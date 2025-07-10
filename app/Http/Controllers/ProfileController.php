<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\UserAvatar;
use App\Services\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ProfileUpdateRequest;

/**
 * Class ProfileController
 * 
 * Handles user profile management operations including:
 * - Profile viewing and editing
 * - Avatar upload and management
 * - Password changes
 * - Email notification preferences
 * - Activity history viewing
 * 
 * @package App\Http\Controllers
 */
class ProfileController extends Controller
{
    /**
     * Avatar service instance
     * 
     * @var AvatarService
     */
    protected AvatarService $avatarService;

    /**
     * Constructor
     * 
     * @param AvatarService $avatarService
     */
    public function __construct(AvatarService $avatarService)
    {
        $this->middleware('auth');
        $this->avatarService = $avatarService;
    }

    /**
     * Display the user's profile page
     * 
     * @return View
     */
    public function show(): View
    {
        try {
            $user = Auth::user()->load(['avatar', 'roles']);
            
            // Get recent activity (last 10 activities)
            $recentActivities = UserActivity::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return view('profile.show', compact('user', 'recentActivities'));
        } catch (Exception $e) {
            Log::error('Error displaying user profile', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('profile.show')->with('error', 'Unable to load profile information.');
        }
    }

    /**
     * Show the profile edit form
     * 
     * @return View
     */
    public function edit(): View
    {
        try {
            $user = Auth::user()->load('avatar');
            
            return view('profile.edit', compact('user'));
        } catch (Exception $e) {
            Log::error('Error displaying profile edit form', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('profile.show')
                ->with('error', 'Unable to load profile edit form.');
        }
    }

    /**
     * Update the user's profile information
     * 
     * @param ProfileUpdateRequest $request
     * @return RedirectResponse
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        try {
            $user = Auth::user();
            $changes = [];

            DB::beginTransaction();

            // Handle password change if requested
            if ($request->isPasswordChangeRequested()) {
                $user->password = Hash::make($request->getNewPassword());
                $user->password_changed_at = now();
                $user->force_password_change = false;
                $changes['password'] = 'Password updated';
            }

            // Update profile fields
            $profileData = $request->getProfileData();
            
            foreach ($profileData as $field => $value) {
                if ($user->$field !== $value) {
                    $oldValue = $user->$field;
                    $user->$field = $value;
                    $changes[$field] = [
                        'old' => $oldValue,
                        'new' => $value
                    ];
                }
            }

            // Save changes
            if (!empty($changes)) {
                $user->updated_by = $user->id;
                $user->save();

                // Log the activity
                UserActivity::create([
                    'user_id' => $user->id,
                    'activity_type' => 'profile_updated',
                    'description' => 'User updated their profile information',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => [
                        'changes' => $changes
                    ]
                ]);

                $message = 'Profile updated successfully.';
            } else {
                $message = 'No changes were made to your profile.';
            }

            DB::commit();

            return redirect()->route('profile.show')
                ->with('success', $message);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating user profile', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'request_data' => $request->except(['_token'])
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update profile. Please try again.')
                ->withInput();
        }
    }

    /**
     * Upload and update user avatar
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        try {
            // Validate the uploaded file
            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpg,jpeg,png|max:2048', // 2MB max
                'crop_data' => 'nullable|json'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file. Please upload a JPG or PNG image under 2MB.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $file = $request->file('avatar');
            $cropData = $request->crop_data ? json_decode($request->crop_data, true) : null;

            DB::beginTransaction();

            // Process the avatar upload
            $avatarData = $this->avatarService->uploadAvatar($user, $file, $cropData);

            // Log the activity
            UserActivity::create([
                'user_id' => $user->id,
                'activity_type' => 'avatar_uploaded',
                'description' => 'User uploaded a new profile picture',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'filename' => $avatarData['filename'],
                    'file_size' => $avatarData['file_size']
                ]
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Avatar uploaded successfully.',
                'avatar_url' => $avatarData['file_url']
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Error uploading avatar', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload avatar. Please try again.'
            ], 500);
        }
    }

    /**
     * Remove user avatar
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function removeAvatar(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            DB::beginTransaction();

            // Remove the avatar
            $this->avatarService->removeAvatar($user);

            // Log the activity
            UserActivity::create([
                'user_id' => $user->id,
                'activity_type' => 'avatar_removed',
                'description' => 'User removed their profile picture',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Avatar removed successfully.'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Error removing avatar', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove avatar. Please try again.'
            ], 500);
        }
    }

    /**
     * Change user password
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function changePassword(Request $request): RedirectResponse
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    Password::min(8)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                ],
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $user = Auth::user();

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return redirect()->back()
                    ->withErrors(['current_password' => 'Current password is incorrect.'])
                    ->withInput();
            }

            // Check if new password is same as current
            if (Hash::check($request->password, $user->password)) {
                return redirect()->back()
                    ->withErrors(['password' => 'New password must be different from current password.'])
                    ->withInput();
            }

            DB::beginTransaction();

            // Update password
            $user->update([
                'password' => Hash::make($request->password),
                'password_changed_at' => now(),
                'force_password_change' => false,
                'updated_by' => $user->id
            ]);

            // Log the activity
            UserActivity::create([
                'user_id' => $user->id,
                'activity_type' => 'password_changed',
                'description' => 'User changed their password',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            DB::commit();

            return redirect()->route('profile.show')
                ->with('success', 'Password changed successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Error changing password', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to change password. Please try again.')
                ->withInput();
        }
    }

    /**
     * Get user activity history
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getActivityHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 15);
            
            $activities = UserActivity::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $activities
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching activity history', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load activity history.'
            ], 500);
        }
    }

    /**
     * Update email notification preferences
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email_notifications' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid notification preference.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            
            DB::beginTransaction();

            $user->update([
                'email_notifications' => $request->boolean('email_notifications'),
                'updated_by' => $user->id
            ]);

            // Log the activity
            UserActivity::create([
                'user_id' => $user->id,
                'activity_type' => 'notification_preferences_updated',
                'description' => 'User updated email notification preferences',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'email_notifications' => $request->boolean('email_notifications')
                ]
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully.'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating notification preferences', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification preferences.'
            ], 500);
        }
    }
}