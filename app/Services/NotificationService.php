<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\UserNotification;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

/**
 * NotificationService
 * 
 * Handles all notification-related operations including creation,
 * targeting, scheduling, delivery tracking, and user interactions.
 * Provides comprehensive notification management for the Analytics Hub system.
 */
class NotificationService
{
    /**
     * Create a new notification.
     * 
     * @param array $data Notification data
     * @return Notification
     * @throws Exception
     */
    public function createNotification(array $data): Notification
    {
        try {
            DB::beginTransaction();

            // Validate required fields
            $this->validateNotificationData($data);

            // Create the notification
            $notification = Notification::create([
                'title' => $data['title'],
                'message' => $data['message'],
                'type' => $data['type'] ?? Notification::TYPE_INFO,
                'category' => $data['category'] ?? null,
                'data' => $data['data'] ?? null,
                'action_data' => $data['action_data'] ?? null,
                'action_url' => $data['action_url'] ?? null,
                'action_text' => $data['action_text'] ?? null,
                'is_important' => $data['is_important'] ?? false,
                'is_dismissible' => $data['is_dismissible'] ?? true,
                'delivery_method' => $data['delivery_method'] ?? 'database',
                'scheduled_at' => isset($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null,
                'expires_at' => isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
                'priority' => $data['priority'] ?? Notification::PRIORITY_NORMAL,
                'sender_id' => $data['sender_id'] ?? Auth::id(),
                'source_type' => $data['source_type'] ?? 'manual',
                'source_reference' => $data['source_reference'] ?? null,
                'icon' => $data['icon'] ?? null,
                'color' => $data['color'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Handle targeting
            if (isset($data['target_type'])) {
                $this->handleNotificationTargeting($notification, $data);
            }

            DB::commit();

            Log::info('Notification created successfully', [
                'notification_id' => $notification->id,
                'title' => $notification->title,
                'type' => $notification->type,
                'created_by' => Auth::id()
            ]);

            return $notification;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create notification', [
                'error' => $e->getMessage(),
                'data' => $data,
                'user_id' => Auth::id()
            ]);
            throw $e;
        }
    }

    /**
     * Create a system-wide notification.
     * 
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $options
     * @return Notification
     */
    public function createSystemNotification(
        string $title, 
        string $message, 
        string $type = Notification::TYPE_SYSTEM,
        array $options = []
    ): Notification {
        $data = array_merge([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'category' => Notification::CATEGORY_SYSTEM,
            'target_type' => 'all',
            'is_important' => true,
            'delivery_method' => 'all',
            'source_type' => 'system'
        ], $options);

        return $this->createNotification($data);
    }

    /**
     * Create a user-specific notification.
     * 
     * @param User|string $user User instance or ID
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $options
     * @return Notification
     */
    public function createUserNotification(
        $user, 
        string $title, 
        string $message, 
        string $type = Notification::TYPE_INFO,
        array $options = []
    ): Notification {
        $userId = $user instanceof User ? $user->id : $user;
        
        $data = array_merge([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'target_type' => 'user',
            'target_users' => [$userId],
            'delivery_method' => 'database'
        ], $options);

        return $this->createNotification($data);
    }

    /**
     * Create a role-based notification.
     * 
     * @param array|string $roles Role names or IDs
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $options
     * @return Notification
     */
    public function createRoleNotification(
        $roles, 
        string $title, 
        string $message, 
        string $type = Notification::TYPE_INFO,
        array $options = []
    ): Notification {
        $data = array_merge([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'target_type' => 'role',
            'target_roles' => is_array($roles) ? $roles : [$roles],
            'delivery_method' => 'database'
        ], $options);

        return $this->createNotification($data);
    }

    /**
     * Schedule a notification for future delivery.
     * 
     * @param array $data
     * @param Carbon $scheduledAt
     * @param Carbon|null $expiresAt
     * @return Notification
     */
    public function scheduleNotification(
        array $data, 
        Carbon $scheduledAt, 
        Carbon $expiresAt = null
    ): Notification {
        $data['scheduled_at'] = $scheduledAt;
        if ($expiresAt) {
            $data['expires_at'] = $expiresAt;
        }

        return $this->createNotification($data);
    }

    /**
     * Handle notification targeting based on target type.
     * 
     * @param Notification $notification
     * @param array $data
     * @return void
     */
    protected function handleNotificationTargeting(Notification $notification, array $data): void
    {
        switch ($data['target_type']) {
            case 'all':
                $this->targetAllUsers($notification);
                break;
            case 'user':
                $this->targetSpecificUsers($notification, $data['target_users'] ?? []);
                break;
            case 'role':
                $this->targetUsersByRole($notification, $data['target_roles'] ?? []);
                break;
            case 'active':
                $this->targetActiveUsers($notification);
                break;
            case 'inactive':
                $this->targetInactiveUsers($notification, $data['inactive_days'] ?? 30);
                break;
            default:
                throw new Exception('Invalid target type: ' . $data['target_type']);
        }
    }

    /**
     * Target all users with the notification.
     * 
     * @param Notification $notification
     * @return void
     */
    protected function targetAllUsers(Notification $notification): void
    {
        $users = User::active()->get();
        $this->createUserNotificationRecords($notification, $users);
    }

    /**
     * Target specific users with the notification.
     * 
     * @param Notification $notification
     * @param array $userIds
     * @return void
     */
    protected function targetSpecificUsers(Notification $notification, array $userIds): void
    {
        $users = User::whereIn('id', $userIds)->active()->get();
        $this->createUserNotificationRecords($notification, $users);
    }

    /**
     * Target users by role with the notification.
     * 
     * @param Notification $notification
     * @param array $roles
     * @return void
     */
    protected function targetUsersByRole(Notification $notification, array $roles): void
    {
        $users = User::active()
            ->whereHas('roles', function ($query) use ($roles) {
                $query->whereIn('name', $roles);
            })
            ->get();
        
        $this->createUserNotificationRecords($notification, $users);
    }

    /**
     * Target active users (logged in within last 30 days).
     * 
     * @param Notification $notification
     * @return void
     */
    protected function targetActiveUsers(Notification $notification): void
    {
        $users = User::active()
            ->where('last_login_at', '>=', now()->subDays(30))
            ->get();
        
        $this->createUserNotificationRecords($notification, $users);
    }

    /**
     * Target inactive users (not logged in for specified days).
     * 
     * @param Notification $notification
     * @param int $days
     * @return void
     */
    protected function targetInactiveUsers(Notification $notification, int $days = 30): void
    {
        $users = User::active()
            ->where(function ($query) use ($days) {
                $query->where('last_login_at', '<', now()->subDays($days))
                      ->orWhereNull('last_login_at');
            })
            ->get();
        
        $this->createUserNotificationRecords($notification, $users);
    }

    /**
     * Create user notification records for targeted users.
     * 
     * @param Notification $notification
     * @param Collection $users
     * @return void
     */
    protected function createUserNotificationRecords(Notification $notification, Collection $users): void
    {
        $records = [];
        $now = now();

        foreach ($users as $user) {
            $records[] = [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'delivery_status' => UserNotification::DELIVERY_STATUS_PENDING,
                'user_priority' => $this->determineUserPriority($notification, $user),
                'max_retries' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($records)) {
            UserNotification::insert($records);
        }
    }

    /**
     * Determine user priority based on notification and user context.
     * 
     * @param Notification $notification
     * @param User $user
     * @return string
     */
    protected function determineUserPriority(Notification $notification, User $user): string
    {
        // High priority for important notifications
        if ($notification->is_important) {
            return UserNotification::PRIORITY_HIGH;
        }

        // High priority for admin users on system notifications
        if ($notification->type === Notification::TYPE_SYSTEM && $user->hasRole('admin')) {
            return UserNotification::PRIORITY_HIGH;
        }

        // Normal priority by default
        return UserNotification::PRIORITY_NORMAL;
    }

    /**
     * Get notifications for a specific user.
     * 
     * @param User|string $user
     * @param array $filters
     * @return Collection
     */
    public function getUserNotifications($user, array $filters = []): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        $query = UserNotification::with(['notification'])
            ->where('user_id', $userId);

        // Apply filters
        if (isset($filters['read_status'])) {
            if ($filters['read_status'] === 'read') {
                $query->read();
            } elseif ($filters['read_status'] === 'unread') {
                $query->unread();
            }
        }

        if (isset($filters['type'])) {
            $query->whereHas('notification', function ($q) use ($filters) {
                $q->where('type', $filters['type']);
            });
        }

        if (isset($filters['category'])) {
            $query->whereHas('notification', function ($q) use ($filters) {
                $q->where('category', $filters['category']);
            });
        }

        if (isset($filters['priority'])) {
            $query->withUserPriority($filters['priority']);
        }

        if (isset($filters['archived'])) {
            if ($filters['archived']) {
                $query->archived();
            } else {
                $query->notArchived();
            }
        }

        if (isset($filters['dismissed'])) {
            if ($filters['dismissed']) {
                $query->dismissed();
            } else {
                $query->notDismissed();
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get unread notification count for a user.
     * 
     * @param User|string $user
     * @return int
     */
    public function getUnreadCount($user): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        return UserNotification::where('user_id', $userId)
            ->unread()
            ->notDismissed()
            ->notArchived()
            ->count();
    }

    /**
     * Mark notification as read for a user.
     * 
     * @param string $notificationId
     * @param User|string $user
     * @return bool
     */
    public function markAsRead(string $notificationId, $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        $userNotification = UserNotification::where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($userNotification) {
            return $userNotification->markAsRead();
        }

        return false;
    }

    /**
     * Mark all notifications as read for a user.
     * 
     * @param User|string $user
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead($user): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        return UserNotification::where('user_id', $userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Dismiss notification for a user.
     * 
     * @param string $notificationId
     * @param User|string $user
     * @return bool
     */
    public function dismissNotification(string $notificationId, $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        $userNotification = UserNotification::where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($userNotification) {
            return $userNotification->dismiss();
        }

        return false;
    }

    /**
     * Delete notification for a user.
     * 
     * @param string $notificationId
     * @param User|string $user
     * @return bool
     */
    public function deleteNotification(string $notificationId, $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        $userNotification = UserNotification::where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($userNotification) {
            $userNotification->delete();
            return true;
        }

        return false;
    }

    /**
     * Process scheduled notifications that are ready to be sent.
     * 
     * @return int Number of notifications processed
     */
    public function processScheduledNotifications(): int
    {
        $notifications = Notification::readyToDeliver()->get();
        $processed = 0;

        foreach ($notifications as $notification) {
            try {
                // Mark as delivered
                $notification->update(['delivered_at' => now()]);
                
                // Update user notification records
                UserNotification::where('notification_id', $notification->id)
                    ->where('delivery_status', UserNotification::DELIVERY_STATUS_PENDING)
                    ->update([
                        'delivery_status' => UserNotification::DELIVERY_STATUS_SENT,
                        'delivered_at' => now(),
                    ]);

                $processed++;

                Log::info('Scheduled notification processed', [
                    'notification_id' => $notification->id,
                    'title' => $notification->title
                ]);

            } catch (Exception $e) {
                Log::error('Failed to process scheduled notification', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $processed;
    }

    /**
     * Clean up expired notifications.
     * 
     * @return int Number of notifications cleaned up
     */
    public function cleanupExpiredNotifications(): int
    {
        $expiredNotifications = Notification::where('expires_at', '<', now())
            ->whereNull('deleted_at')
            ->get();

        $cleaned = 0;

        foreach ($expiredNotifications as $notification) {
            try {
                // Soft delete the notification
                $notification->delete();
                
                // Archive user notification records
                UserNotification::where('notification_id', $notification->id)
                    ->update([
                        'is_archived' => true,
                        'archived_at' => now(),
                    ]);

                $cleaned++;

            } catch (Exception $e) {
                Log::error('Failed to cleanup expired notification', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $cleaned;
    }

    /**
     * Get notification statistics.
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total_notifications' => Notification::count(),
            'active_notifications' => Notification::whereNull('deleted_at')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->count(),
            'scheduled_notifications' => Notification::scheduled()->count(),
            'expired_notifications' => Notification::where('expires_at', '<', now())->count(),
            'total_user_notifications' => UserNotification::count(),
            'unread_notifications' => UserNotification::unread()->count(),
            'read_notifications' => UserNotification::read()->count(),
            'dismissed_notifications' => UserNotification::dismissed()->count(),
            'failed_deliveries' => UserNotification::failed()->count(),
        ];
    }

    /**
     * Validate notification data.
     * 
     * @param array $data
     * @return void
     * @throws Exception
     */
    protected function validateNotificationData(array $data): void
    {
        if (empty($data['title'])) {
            throw new Exception('Notification title is required');
        }

        if (empty($data['message'])) {
            throw new Exception('Notification message is required');
        }

        if (isset($data['type']) && !in_array($data['type'], [
            Notification::TYPE_INFO,
            Notification::TYPE_SUCCESS,
            Notification::TYPE_WARNING,
            Notification::TYPE_ERROR,
            Notification::TYPE_SYSTEM
        ])) {
            throw new Exception('Invalid notification type');
        }

        if (isset($data['priority']) && !in_array($data['priority'], [
            Notification::PRIORITY_LOW,
            Notification::PRIORITY_NORMAL,
            Notification::PRIORITY_HIGH,
            Notification::PRIORITY_URGENT
        ])) {
            throw new Exception('Invalid notification priority');
        }

        if (isset($data['scheduled_at']) && Carbon::parse($data['scheduled_at'])->isPast()) {
            throw new Exception('Scheduled time must be in the future');
        }

        if (isset($data['expires_at']) && isset($data['scheduled_at'])) {
            $scheduledAt = Carbon::parse($data['scheduled_at']);
            $expiresAt = Carbon::parse($data['expires_at']);
            
            if ($expiresAt->lte($scheduledAt)) {
                throw new Exception('Expiry time must be after scheduled time');
            }
        }
    }
}