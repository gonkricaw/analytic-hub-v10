<?php

namespace App\Services;

use App\Models\User;
use App\Models\SystemConfig;
use App\Models\Notification;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * TermsNotificationService
 * 
 * Handles Terms & Conditions update notifications and version management.
 * Manages user notification when T&C are updated and tracks acceptance status.
 */
class TermsNotificationService
{
    /**
     * Get current T&C version from system configuration
     * 
     * @return string
     */
    public function getCurrentVersion(): string
    {
        return SystemConfig::get('terms.current_version', '1.0');
    }

    /**
     * Update T&C version and trigger notifications
     * 
     * @param string $newVersion
     * @param string|null $reason
     * @return bool
     */
    public function updateVersion(string $newVersion, ?string $reason = null): bool
    {
        try {
            DB::beginTransaction();

            $currentVersion = $this->getCurrentVersion();
            
            // Update version in system config
            SystemConfig::set('terms.current_version', $newVersion, $reason);
            SystemConfig::set('terms.last_updated', now()->toDateTimeString());

            // Log the version update
            ActivityLog::create([
                'causer_id' => auth()->id(),
                'event' => 'updated',
                'action' => 'terms_version_updated',
                'description' => "Terms & Conditions updated from version {$currentVersion} to {$newVersion}",
                'subject_type' => 'SystemConfig',
                'subject_id' => null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'severity' => 'medium',
                'properties' => json_encode([
                    'old_version' => $currentVersion,
                    'new_version' => $newVersion,
                    'reason' => $reason
                ])
            ]);

            // Send notifications if enabled
            if (SystemConfig::get('terms.notification_enabled', true)) {
                $this->notifyUsersOfUpdate($newVersion, $currentVersion);
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update T&C version', [
                'new_version' => $newVersion,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send notifications to all users about T&C update
     * 
     * @param string $newVersion
     * @param string $oldVersion
     * @return int Number of notifications sent
     */
    public function notifyUsersOfUpdate(string $newVersion, string $oldVersion): int
    {
        $title = SystemConfig::get('terms.notification_title', 'Terms & Conditions Updated');
        $message = SystemConfig::get('terms.notification_message', 
            'Our Terms & Conditions have been updated. Please review and accept the new terms to continue using the system.');

        // Get all active users
        $users = User::where('status', 'active')
                    ->where('email_notifications', true)
                    ->get();

        $notificationCount = 0;

        foreach ($users as $user) {
            try {
                // Create notification
                Notification::create([
                    'user_id' => $user->id,
                    'title' => $title,
                    'message' => $message,
                    'type' => Notification::TYPE_SYSTEM,
                    'category' => Notification::CATEGORY_TERMS,
                    'data' => [
                        'old_version' => $oldVersion,
                        'new_version' => $newVersion,
                        'requires_acceptance' => SystemConfig::get('terms.force_reacceptance', true)
                    ],
                    'action_url' => route('terms.accept'),
                    'action_text' => 'Review Terms',
                    'is_read' => false,
                    'priority' => 'high'
                ]);

                $notificationCount++;

            } catch (\Exception $e) {
                Log::error('Failed to create T&C notification for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('T&C update notifications sent', [
            'new_version' => $newVersion,
            'old_version' => $oldVersion,
            'notifications_sent' => $notificationCount,
            'total_users' => $users->count()
        ]);

        return $notificationCount;
    }

    /**
     * Check if user needs to re-accept T&C
     * 
     * @param User $user
     * @return bool
     */
    public function userNeedsReacceptance(User $user): bool
    {
        // If force reacceptance is disabled, no need to check
        if (!SystemConfig::get('terms.force_reacceptance', true)) {
            return false;
        }

        $currentVersion = $this->getCurrentVersion();
        $userAcceptedVersion = $user->terms_version_accepted;

        // If user hasn't accepted any version, they need to accept
        if (!$user->terms_accepted || !$userAcceptedVersion) {
            return true;
        }

        // If user's accepted version is different from current, they need to re-accept
        return $userAcceptedVersion !== $currentVersion;
    }

    /**
     * Mark user as having accepted current T&C version
     * 
     * @param User $user
     * @return bool
     */
    public function markUserAcceptance(User $user): bool
    {
        try {
            $currentVersion = $this->getCurrentVersion();
            
            $user->update([
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
                'terms_version_accepted' => $currentVersion
            ]);

            // Log the acceptance
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'terms_accepted',
                'description' => "User accepted Terms & Conditions version {$currentVersion}",
                'model_type' => 'User',
                'model_id' => $user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'severity' => 'low',
                'context' => [
                    'version' => $currentVersion,
                    'previous_version' => $user->getOriginal('terms_version_accepted')
                ]
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to mark user T&C acceptance', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get users who need to re-accept T&C
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersNeedingReacceptance()
    {
        $currentVersion = $this->getCurrentVersion();
        
        return User::where('status', 'active')
                  ->where(function ($query) use ($currentVersion) {
                      $query->where('terms_accepted', false)
                            ->orWhere('terms_version_accepted', '!=', $currentVersion)
                            ->orWhereNull('terms_version_accepted');
                  })
                  ->get();
    }

    /**
     * Get T&C update statistics
     * 
     * @return array
     */
    public function getUpdateStatistics(): array
    {
        $currentVersion = $this->getCurrentVersion();
        $totalUsers = User::where('status', 'active')->count();
        $usersAccepted = User::where('status', 'active')
                            ->where('terms_accepted', true)
                            ->where('terms_version_accepted', $currentVersion)
                            ->count();
        $usersNeedingAcceptance = $totalUsers - $usersAccepted;

        return [
            'current_version' => $currentVersion,
            'last_updated' => SystemConfig::get('terms.last_updated'),
            'total_users' => $totalUsers,
            'users_accepted' => $usersAccepted,
            'users_needing_acceptance' => $usersNeedingAcceptance,
            'acceptance_rate' => $totalUsers > 0 ? round(($usersAccepted / $totalUsers) * 100, 2) : 0,
            'notification_enabled' => SystemConfig::get('terms.notification_enabled', true),
            'force_reacceptance' => SystemConfig::get('terms.force_reacceptance', true)
        ];
    }

    /**
     * Clean up old T&C notifications
     * 
     * @param int $daysOld
     * @return int Number of notifications cleaned up
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);
        
        $deletedCount = Notification::where('category', Notification::CATEGORY_TERMS)
                                   ->where('created_at', '<', $cutoffDate)
                                   ->where('is_read', true)
                                   ->delete();

        Log::info('Cleaned up old T&C notifications', [
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate->toDateTimeString()
        ]);

        return $deletedCount;
    }
}