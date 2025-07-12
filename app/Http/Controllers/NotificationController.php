<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\UserNotification;
use App\Models\User;
use App\Models\Role;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * NotificationController
 * 
 * Handles all notification-related HTTP requests including:
 * - Notification management interface
 * - Creating and editing notifications
 * - User notification interactions
 * - Notification statistics and reporting
 */
class NotificationController extends Controller
{
    /**
     * The notification service instance.
     */
    protected NotificationService $notificationService;

    /**
     * Create a new controller instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth');
        $this->middleware('permission:manage-notifications')->except([
            'index', 'show', 'markAsRead', 'markAllAsRead', 'dismiss', 'getUserNotifications'
        ]);
        
        $this->notificationService = $notificationService;
    }

    /**
     * Display the notification management interface.
     */
    public function index(): View
    {
        $statistics = $this->notificationService->getStatistics();
        
        return view('admin.notifications.index', compact('statistics'));
    }

    /**
     * Get notifications data for DataTables.
     */
    public function data(Request $request): JsonResponse
    {
        try {
            $query = Notification::with(['sender', 'creator'])
                ->withCount('userNotifications');

            // Apply filters
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->filled('status')) {
                switch ($request->status) {
                    case 'scheduled':
                        $query->scheduled();
                        break;
                    case 'expired':
                        $query->where('expires_at', '<', now());
                        break;
                    case 'active':
                        $query->whereNull('deleted_at')
                              ->where(function ($q) {
                                  $q->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', now());
                              });
                        break;
                }
            }

            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', Carbon::parse($request->date_from));
            }

            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
            }

            // Search
            if ($request->filled('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            // Ordering
            $orderColumn = $request->input('order.0.column', 0);
            $orderDirection = $request->input('order.0.dir', 'desc');
            
            $columns = ['id', 'title', 'type', 'priority', 'created_at', 'scheduled_at', 'expires_at'];
            if (isset($columns[$orderColumn])) {
                $query->orderBy($columns[$orderColumn], $orderDirection);
            }

            // Pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 25);
            
            $totalRecords = $query->count();
            $notifications = $query->skip($start)->take($length)->get();

            $data = $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => str_limit(strip_tags($notification->message), 100),
                    'type' => $notification->type,
                    'category' => $notification->category,
                    'priority' => $notification->priority,
                    'is_important' => $notification->is_important,
                    'recipients_count' => $notification->user_notifications_count,
                    'sender' => $notification->sender ? $notification->sender->name : 'System',
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'scheduled_at' => $notification->scheduled_at ? $notification->scheduled_at->format('Y-m-d H:i:s') : null,
                    'expires_at' => $notification->expires_at ? $notification->expires_at->format('Y-m-d H:i:s') : null,
                    'status' => $this->getNotificationStatus($notification),
                    'actions' => $this->getNotificationActions($notification),
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $data
            ]);

        } catch (Exception $e) {
            Log::error('Failed to fetch notifications data', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'error' => 'Failed to fetch notifications data'
            ], 500);
        }
    }

    /**
     * Show the form for creating a new notification.
     */
    public function create(): View
    {
        $users = User::active()->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        
        return view('admin.notifications.create', compact('users', 'roles'));
    }

    /**
     * Store a newly created notification.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'type' => 'required|in:info,success,warning,error,system',
                'category' => 'nullable|string|max:100',
                'priority' => 'required|in:low,normal,high,urgent',
                'target_type' => 'required|in:all,user,role,active,inactive',
                'target_users' => 'required_if:target_type,user|array',
                'target_users.*' => 'exists:users,id',
                'target_roles' => 'required_if:target_type,role|array',
                'target_roles.*' => 'exists:roles,name',
                'inactive_days' => 'required_if:target_type,inactive|integer|min:1|max:365',
                'delivery_method' => 'required|in:database,email,sms,push,all',
                'is_important' => 'boolean',
                'is_dismissible' => 'boolean',
                'scheduled_at' => 'nullable|date|after:now',
                'expires_at' => 'nullable|date|after:scheduled_at',
                'action_url' => 'nullable|url',
                'action_text' => 'nullable|string|max:100',
                'icon' => 'nullable|string|max:100',
                'color' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();
            $data['source_type'] = 'admin';
            $data['created_by'] = Auth::id();

            $notification = $this->notificationService->createNotification($data);

            return response()->json([
                'success' => true,
                'message' => 'Notification created successfully',
                'notification' => $notification
            ]);

        } catch (Exception $e) {
            Log::error('Failed to create notification', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified notification.
     */
    public function show(string $id): View
    {
        $notification = Notification::with(['sender', 'creator', 'userNotifications.user'])
            ->findOrFail($id);

        $statistics = [
            'total_recipients' => $notification->userNotifications()->count(),
            'read_count' => $notification->userNotifications()->read()->count(),
            'unread_count' => $notification->userNotifications()->unread()->count(),
            'dismissed_count' => $notification->userNotifications()->dismissed()->count(),
            'delivered_count' => $notification->userNotifications()
                ->where('delivery_status', UserNotification::DELIVERY_STATUS_DELIVERED)
                ->count(),
            'failed_count' => $notification->userNotifications()
                ->where('delivery_status', UserNotification::DELIVERY_STATUS_FAILED)
                ->count(),
        ];

        return view('admin.notifications.show', compact('notification', 'statistics'));
    }

    /**
     * Show the form for editing the specified notification.
     */
    public function edit(string $id): View
    {
        $notification = Notification::findOrFail($id);
        $users = User::active()->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        
        return view('admin.notifications.edit', compact('notification', 'users', 'roles'));
    }

    /**
     * Update the specified notification.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $notification = Notification::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'type' => 'required|in:info,success,warning,error,system',
                'category' => 'nullable|string|max:100',
                'priority' => 'required|in:low,normal,high,urgent',
                'delivery_method' => 'required|in:database,email,sms,push,all',
                'is_important' => 'boolean',
                'is_dismissible' => 'boolean',
                'scheduled_at' => 'nullable|date',
                'expires_at' => 'nullable|date|after:scheduled_at',
                'action_url' => 'nullable|url',
                'action_text' => 'nullable|string|max:100',
                'icon' => 'nullable|string|max:100',
                'color' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $notification->update(array_merge($request->all(), [
                'updated_by' => Auth::id()
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Notification updated successfully',
                'notification' => $notification
            ]);

        } catch (Exception $e) {
            Log::error('Failed to update notification', [
                'notification_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified notification.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $notification = Notification::findOrFail($id);
            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to delete notification', [
                'notification_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user notifications for the authenticated user.
     */
    public function getUserNotifications(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['read_status', 'type', 'category', 'priority', 'archived', 'dismissed']);
            $notifications = $this->notificationService->getUserNotifications(Auth::user(), $filters);
            
            $data = $notifications->map(function ($userNotification) {
                $notification = $userNotification->notification;
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'category' => $notification->category,
                    'priority' => $notification->priority,
                    'is_important' => $notification->is_important,
                    'is_dismissible' => $notification->is_dismissible,
                    'action_url' => $notification->action_url,
                    'action_text' => $notification->action_text,
                    'icon' => $notification->icon,
                    'color' => $notification->color,
                    'is_read' => $userNotification->is_read,
                    'read_at' => $userNotification->read_at,
                    'is_dismissed' => $userNotification->is_dismissed,
                    'is_pinned' => $userNotification->is_pinned,
                    'is_starred' => $userNotification->is_starred,
                    'user_priority' => $userNotification->user_priority,
                    'created_at' => $notification->created_at,
                    'age' => $notification->age,
                ];
            });

            return response()->json([
                'success' => true,
                'notifications' => $data,
                'unread_count' => $this->notificationService->getUnreadCount(Auth::user())
            ]);

        } catch (Exception $e) {
            Log::error('Failed to fetch user notifications', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications'
            ], 500);
        }
    }

    /**
     * Mark a notification as read for the authenticated user.
     */
    public function markAsRead(string $id): JsonResponse
    {
        try {
            $success = $this->notificationService->markAsRead($id, Auth::user());
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification marked as read',
                    'unread_count' => $this->notificationService->getUnreadCount(Auth::user())
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Notification not found or already read'
            ], 404);

        } catch (Exception $e) {
            Log::error('Failed to mark notification as read', [
                'notification_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $count = $this->notificationService->markAllAsRead(Auth::user());
            
            return response()->json([
                'success' => true,
                'message' => "Marked {$count} notifications as read",
                'unread_count' => 0
            ]);

        } catch (Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read'
            ], 500);
        }
    }

    /**
     * Dismiss a notification for the authenticated user.
     */
    public function dismiss(string $id): JsonResponse
    {
        try {
            $success = $this->notificationService->dismissNotification($id, Auth::user());
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification dismissed',
                    'unread_count' => $this->notificationService->getUnreadCount(Auth::user())
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);

        } catch (Exception $e) {
            Log::error('Failed to dismiss notification', [
                'notification_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to dismiss notification'
            ], 500);
        }
    }

    /**
     * Get notification statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->notificationService->getStatistics();
            
            return response()->json([
                'success' => true,
                'statistics' => $statistics
            ]);

        } catch (Exception $e) {
            Log::error('Failed to fetch notification statistics', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics'
            ], 500);
        }
    }

    /**
     * Get notification status.
     */
    protected function getNotificationStatus(Notification $notification): string
    {
        if ($notification->deleted_at) {
            return 'deleted';
        }
        
        if ($notification->expires_at && $notification->expires_at->isPast()) {
            return 'expired';
        }
        
        if ($notification->scheduled_at && $notification->scheduled_at->isFuture()) {
            return 'scheduled';
        }
        
        return 'active';
    }

    /**
     * Get notification actions.
     */
    protected function getNotificationActions(Notification $notification): array
    {
        $actions = [];
        
        $actions[] = [
            'type' => 'view',
            'url' => route('admin.notifications.show', $notification->id),
            'icon' => 'fas fa-eye',
            'title' => 'View'
        ];
        
        if (Auth::user()->can('manage-notifications')) {
            $actions[] = [
                'type' => 'edit',
                'url' => route('admin.notifications.edit', $notification->id),
                'icon' => 'fas fa-edit',
                'title' => 'Edit'
            ];
            
            $actions[] = [
                'type' => 'delete',
                'url' => route('admin.notifications.destroy', $notification->id),
                'icon' => 'fas fa-trash',
                'title' => 'Delete',
                'confirm' => 'Are you sure you want to delete this notification?'
            ];
        }
        
        return $actions;
    }
}