<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\UserInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * UserController
 * 
 * Handles comprehensive user management operations for the Analytics Hub system.
 * Provides CRUD operations, user status management, bulk operations, and DataTables integration.
 * 
 * Features:
 * - User listing with DataTables and advanced filtering
 * - User creation with temporary password generation
 * - User editing (admin only)
 * - User suspension/activation
 * - Soft delete functionality
 * - Bulk operations for multiple users
 * - Search and filtering capabilities
 * - Activity logging for all operations
 * 
 * @package App\Http\Controllers\Admin
 */
class UserController extends Controller
{
    /**
     * The user invitation service
     */
    protected UserInvitationService $invitationService;

    /**
     * Create a new controller instance.
     * 
     * @param UserInvitationService $invitationService
     */
    public function __construct(UserInvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }
    /**
     * Display a listing of users with DataTables support.
     * 
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Check if this is a DataTables AJAX request
        if ($request->ajax()) {
            return $this->getUsersDataTable($request);
        }

        // Get roles for filter dropdown
        $roles = Role::where('status', 'active')
                    ->orderBy('display_name')
                    ->get(['id', 'name', 'display_name']);

        return view('admin.users.index', compact('roles'));
    }

    /**
     * Get users data for DataTables.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function getUsersDataTable(Request $request)
    {
        $query = User::with(['roles:id,name,display_name', 'avatar:user_id,file_path'])
                    ->select([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'username',
                        'status',
                        'department',
                        'position',
                        'last_login_at',
                        'last_seen_at',
                        'email_verified_at',
                        'is_first_login',
                        'created_at',
                        'updated_at'
                    ]);

        return DataTables::of($query)
            ->addColumn('full_name', function ($user) {
                return $user->full_name;
            })
            ->addColumn('avatar', function ($user) {
                if ($user->avatar && $user->avatar->file_path) {
                    return '<img src="' . asset('storage/' . $user->avatar->file_path) . '" class="rounded-circle" width="32" height="32" alt="Avatar">';
                }
                return '<div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 14px; color: white;">' . strtoupper(substr($user->first_name, 0, 1)) . '</div>';
            })
            ->addColumn('roles', function ($user) {
                return $user->roles->map(function ($role) {
                    return '<span class="badge bg-primary me-1">' . $role->display_name . '</span>';
                })->implode('');
            })
            ->addColumn('status_badge', function ($user) {
                $statusClasses = [
                    'active' => 'bg-success',
                    'suspended' => 'bg-danger',
                    'pending' => 'bg-warning text-dark',
                    'deleted' => 'bg-secondary'
                ];
                $class = $statusClasses[$user->status] ?? 'bg-secondary';
                return '<span class="badge ' . $class . '">' . ucfirst($user->status) . '</span>';
            })
            ->addColumn('last_activity', function ($user) {
                if ($user->last_seen_at) {
                    return '<span title="' . $user->last_seen_at->format('Y-m-d H:i:s') . '">' . $user->last_seen_at->diffForHumans() . '</span>';
                }
                return '<span class="text-muted">Never</span>';
            })
            ->addColumn('verification_status', function ($user) {
                if ($user->email_verified_at) {
                    return '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Verified</span>';
                }
                return '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-circle me-1"></i>Unverified</span>';
            })
            ->addColumn('actions', function ($user) {
                $actions = '<div class="btn-group" role="group">';
                
                // View button
                $actions .= '<a href="' . route('admin.users.show', $user->id) . '" class="btn btn-sm btn-info" title="View Details">';
                $actions .= '<i class="fas fa-eye"></i></a>';
                
                // Edit button
                $actions .= '<a href="' . route('admin.users.edit', $user->id) . '" class="btn btn-sm btn-warning" title="Edit User">';
                $actions .= '<i class="fas fa-edit"></i></a>';
                
                // Status toggle button
                if ($user->status === 'active') {
                    $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="toggleUserStatus(\'' . $user->id . '\', \'suspended\')" title="Suspend User">';
                    $actions .= '<i class="fas fa-ban"></i></button>';
                } elseif ($user->status === 'suspended') {
                    $actions .= '<button type="button" class="btn btn-sm btn-success" onclick="toggleUserStatus(\'' . $user->id . '\', \'active\')" title="Activate User">';
                    $actions .= '<i class="fas fa-check"></i></button>';
                }
                
                // Delete button (soft delete)
                if ($user->status !== 'deleted') {
                    $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteUser(\'' . $user->id . '\')" title="Delete User">';
                    $actions .= '<i class="fas fa-trash"></i></button>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->filter(function ($query) use ($request) {
                // Status filter
                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }
                
                // Role filter
                if ($request->filled('role')) {
                    $query->whereHas('roles', function ($q) use ($request) {
                        $q->where('role_id', $request->role);
                    });
                }
                
                // Department filter
                if ($request->filled('department')) {
                    $query->where('department', 'like', '%' . $request->department . '%');
                }
                
                // Verification status filter
                if ($request->filled('verified')) {
                    if ($request->verified === 'yes') {
                        $query->whereNotNull('email_verified_at');
                    } else {
                        $query->whereNull('email_verified_at');
                    }
                }
                
                // Global search
                if ($request->filled('search.value')) {
                    $searchValue = $request->input('search.value');
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('first_name', 'like', '%' . $searchValue . '%')
                          ->orWhere('last_name', 'like', '%' . $searchValue . '%')
                          ->orWhere('email', 'like', '%' . $searchValue . '%')
                          ->orWhere('username', 'like', '%' . $searchValue . '%')
                          ->orWhere('department', 'like', '%' . $searchValue . '%')
                          ->orWhere('position', 'like', '%' . $searchValue . '%');
                    });
                }
            })
            ->rawColumns(['avatar', 'roles', 'status_badge', 'last_activity', 'verification_status', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new user.
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $roles = Role::where('status', 'active')
                    ->orderBy('display_name')
                    ->get(['id', 'name', 'display_name']);

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = $this->validateUserData($request);
        
        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            DB::beginTransaction();

            // Generate temporary password (8 characters: uppercase, lowercase, numbers)
            $temporaryPassword = $this->generateTemporaryPassword();

            // Create the user
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($temporaryPassword),
                'status' => User::STATUS_PENDING,
                'is_first_login' => true,
                'force_password_change' => true,
                'email_notifications' => $request->boolean('email_notifications', true),
                'bio' => $request->bio,
                'phone' => $request->phone,
                'department' => $request->department,
                'position' => $request->position,
                'created_by' => auth()->id(),
            ]);

            // Assign roles
            if ($request->filled('roles')) {
                $user->roles()->attach($request->roles);
            }

            // Log the activity
            $this->logUserActivity('user_created', $user, [
                'temporary_password' => '[REDACTED]',
                'roles_assigned' => $request->roles ?? [],
                'created_by' => auth()->user()->full_name
            ]);

            DB::commit();

            // Send invitation email with temporary password
            $invitationResult = $this->invitationService->sendInvitation(
                $user,
                auth()->user(),
                $request->custom_message,
                $request->template_id
            );

            if ($invitationResult['success']) {
                return redirect()->route('admin.users.index')
                               ->with('success', 'User created successfully and invitation email sent.');
            } else {
                // User created but email failed - still show success but with warning
                return redirect()->route('admin.users.index')
                               ->with('warning', 'User created successfully, but invitation email failed to send. You can resend it from the user management page.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User creation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->except(['password'])
            ]);

            return redirect()->back()
                           ->with('error', 'Failed to create user. Please try again.')
                           ->withInput();
        }
    }

    /**
     * Display the specified user.
     * 
     * @param User $user
     * @return \Illuminate\View\View
     */
    public function show(User $user)
    {
        $user->load([
            'roles:id,name,display_name',
            'avatar:user_id,file_path,file_name',
            'activities' => function ($query) {
                $query->latest()->limit(10);
            }
        ]);

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     * 
     * @param User $user
     * @return \Illuminate\View\View
     */
    public function edit(User $user)
    {
        $roles = Role::where('status', 'active')
                    ->orderBy('display_name')
                    ->get(['id', 'name', 'display_name']);

        $user->load('roles:id,name,display_name');

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     * 
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $user)
    {
        // Validate the request
        $validator = $this->validateUserData($request, $user->id);
        
        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            DB::beginTransaction();

            // Store old values for logging
            $oldValues = $user->only([
                'first_name', 'last_name', 'email', 'username',
                'bio', 'phone', 'department', 'position', 'email_notifications'
            ]);
            $oldRoles = $user->roles->pluck('id')->toArray();

            // Update the user
            $user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'username' => $request->username,
                'email_notifications' => $request->boolean('email_notifications'),
                'bio' => $request->bio,
                'phone' => $request->phone,
                'department' => $request->department,
                'position' => $request->position,
                'updated_by' => auth()->id(),
            ]);

            // Update roles
            if ($request->filled('roles')) {
                $user->roles()->sync($request->roles);
            } else {
                $user->roles()->detach();
            }

            // Log the activity
            $this->logUserActivity('user_updated', $user, [
                'old_values' => $oldValues,
                'new_values' => $user->only([
                    'first_name', 'last_name', 'email', 'username',
                    'bio', 'phone', 'department', 'position', 'email_notifications'
                ]),
                'old_roles' => $oldRoles,
                'new_roles' => $request->roles ?? [],
                'updated_by' => auth()->user()->full_name
            ]);

            DB::commit();

            return redirect()->route('admin.users.show', $user)
                           ->with('success', 'User updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'request_data' => $request->except(['password'])
            ]);

            return redirect()->back()
                           ->with('error', 'Failed to update user. Please try again.')
                           ->withInput();
        }
    }

    /**
     * Remove the specified user from storage (soft delete).
     * 
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user)
    {
        try {
            DB::beginTransaction();

            // Update status to deleted and soft delete
            $user->update([
                'status' => User::STATUS_DELETED,
                'updated_by' => auth()->id()
            ]);
            $user->delete();

            // Log the activity
            $this->logUserActivity('user_deleted', $user, [
                'deleted_by' => auth()->user()->full_name,
                'deletion_type' => 'soft_delete'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user. Please try again.'
            ], 500);
        }
    }

    /**
     * Toggle user status (activate/suspend).
     * 
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, User $user)
    {
        $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])]
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $user->status;
            $newStatus = $request->status;

            $user->update([
                'status' => $newStatus,
                'updated_by' => auth()->id()
            ]);

            // Log the activity
            $this->logUserActivity('user_status_changed', $user, [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => auth()->user()->full_name
            ]);

            DB::commit();

            $message = $newStatus === 'active' ? 'User activated successfully.' : 'User suspended successfully.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'new_status' => $newStatus
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User status toggle failed', [
                'user_id' => $user->id,
                'requested_status' => $request->status,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle bulk operations on multiple users.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => ['required', Rule::in(['activate', 'suspend', 'delete'])],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'string', 'exists:idbi_users,id']
        ]);

        try {
            DB::beginTransaction();

            $userIds = $request->user_ids;
            $action = $request->action;
            $affectedCount = 0;

            $users = User::whereIn('id', $userIds)->get();

            foreach ($users as $user) {
                $oldStatus = $user->status;
                
                switch ($action) {
                    case 'activate':
                        if ($user->status !== 'active') {
                            $user->update(['status' => User::STATUS_ACTIVE, 'updated_by' => auth()->id()]);
                            $affectedCount++;
                        }
                        break;
                        
                    case 'suspend':
                        if ($user->status !== 'suspended') {
                            $user->update(['status' => User::STATUS_SUSPENDED, 'updated_by' => auth()->id()]);
                            $affectedCount++;
                        }
                        break;
                        
                    case 'delete':
                        if ($user->status !== 'deleted') {
                            $user->update(['status' => User::STATUS_DELETED, 'updated_by' => auth()->id()]);
                            $user->delete();
                            $affectedCount++;
                        }
                        break;
                }

                // Log individual user activity
                $this->logUserActivity('user_bulk_' . $action, $user, [
                    'old_status' => $oldStatus,
                    'new_status' => $user->status,
                    'bulk_action' => true,
                    'performed_by' => auth()->user()->full_name
                ]);
            }

            DB::commit();

            $actionMessages = [
                'activate' => 'activated',
                'suspend' => 'suspended',
                'delete' => 'deleted'
            ];

            return response()->json([
                'success' => true,
                'message' => "{$affectedCount} user(s) {$actionMessages[$action]} successfully.",
                'affected_count' => $affectedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk user action failed', [
                'action' => $request->action,
                'user_ids' => $request->user_ids,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk action. Please try again.'
            ], 500);
        }
    }

    /**
     * Validate user data for create/update operations.
     * 
     * @param Request $request
     * @param string|null $userId
     * @return \Illuminate\Validation\Validator
     */
    private function validateUserData(Request $request, ?string $userId = null)
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('idbi_users', 'email')->ignore($userId)
            ],
            'username' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('idbi_users', 'username')->ignore($userId)
            ],
            'bio' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'email_notifications' => ['boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:idbi_roles,id']
        ];

        $messages = [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'username.unique' => 'This username is already taken.',
            'username.regex' => 'Username can only contain letters, numbers, dots, underscores, and hyphens.',
            'roles.*.exists' => 'One or more selected roles are invalid.'
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Generate a temporary password (8 characters: uppercase, lowercase, numbers).
     * 
     * @return string
     */
    private function generateTemporaryPassword(): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        
        // Ensure at least one character from each category
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        
        // Fill the remaining 5 characters randomly
        $allChars = $uppercase . $lowercase . $numbers;
        for ($i = 0; $i < 5; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password to randomize character positions
        return str_shuffle($password);
    }

    /**
     * Log user-related activity.
     * 
     * @param string $activityType
     * @param User $user
     * @param array $properties
     * @return void
     */
    private function logUserActivity(string $activityType, User $user, array $properties = []): void
    {
        try {
            \App\Models\UserActivity::create([
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email,
                'user_name' => auth()->user()->full_name,
                'activity_type' => $activityType,
                'activity_name' => 'User Management',
                'description' => $this->getActivityDescription($activityType, $user),
                'subject_type' => 'App\\Models\\User',
                'subject_id' => $user->id,
                'properties' => array_merge($properties, [
                    'target_user_id' => $user->id,
                    'target_user_email' => $user->email,
                    'target_user_name' => $user->full_name
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'route' => request()->route()?->getName(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'severity' => $this->getActivitySeverity($activityType),
                'category' => 'user_management'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log user activity', [
                'activity_type' => $activityType,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get activity description based on type.
     * 
     * @param string $activityType
     * @param User $user
     * @return string
     */
    private function getActivityDescription(string $activityType, User $user): string
    {
        $descriptions = [
            'user_created' => "Created new user: {$user->full_name} ({$user->email})",
            'user_updated' => "Updated user: {$user->full_name} ({$user->email})",
            'user_deleted' => "Deleted user: {$user->full_name} ({$user->email})",
            'user_status_changed' => "Changed status for user: {$user->full_name} ({$user->email})",
            'user_bulk_activate' => "Bulk activated user: {$user->full_name} ({$user->email})",
            'user_bulk_suspend' => "Bulk suspended user: {$user->full_name} ({$user->email})",
            'user_bulk_delete' => "Bulk deleted user: {$user->full_name} ({$user->email})"
        ];

        return $descriptions[$activityType] ?? "User management action: {$activityType}";
    }

    /**
     * Get activity severity level.
     * 
     * @param string $activityType
     * @return string
     */
    private function getActivitySeverity(string $activityType): string
    {
        $severityMap = [
            'user_created' => 'medium',
            'user_updated' => 'low',
            'user_deleted' => 'high',
            'user_status_changed' => 'medium',
            'user_bulk_activate' => 'medium',
            'user_bulk_suspend' => 'high',
            'user_bulk_delete' => 'high'
        ];

        return $severityMap[$activityType] ?? 'low';
    }
}