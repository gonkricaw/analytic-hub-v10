<?php

// Purpose: Handle password change functionality for first login and expired passwords
// Related Feature: Password Management - Force Password Change
// Dependencies: User model, PasswordValidationService, PasswordHistory model

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PasswordHistory;
use App\Models\User;
use App\Services\PasswordValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * PasswordChangeController
 * 
 * Handles password change operations for:
 * - First-time login password changes
 * - Expired password changes
 * - Force password change scenarios
 * 
 * Integrates with PasswordValidationService for policy enforcement
 * and PasswordHistory for tracking password changes.
 * 
 * @package App\Http\Controllers
 */
class PasswordChangeController extends Controller
{
    /**
     * Password validation service instance
     * 
     * @var PasswordValidationService
     */
    protected $passwordService;

    /**
     * Constructor
     * 
     * @param PasswordValidationService $passwordService
     */
    public function __construct(PasswordValidationService $passwordService)
    {
        $this->passwordService = $passwordService;
        $this->middleware('auth');
    }

    /**
     * Show first-time login password change form
     * 
     * Displays the password change form for users who need to change
     * their password on first login or when forced by admin.
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showFirstChangeForm(Request $request)
    {
        $user = Auth::user();
        
        // Verify user needs to change password
        if (!$user->is_first_login && !$user->force_password_change && $user->status !== 'pending') {
            return redirect()->route('dashboard')
                ->with('info', 'Password change not required.');
        }

        // Log access to password change form
        Log::info('User accessing first-time password change form', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_first_login' => $user->is_first_login,
            'force_password_change' => $user->force_password_change,
            'status' => $user->status
        ]);

        return view('auth.first-password-change', [
            'user' => $user,
            'isFirstLogin' => $user->is_first_login || $user->status === 'pending',
            'isForced' => $user->force_password_change
        ]);
    }

    /**
     * Handle first-time login password change
     * 
     * Processes the password change request for first-time login users.
     * Validates the new password, updates user record, and logs the change.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateFirstChange(Request $request)
    {
        $user = Auth::user();
        
        // Verify user needs to change password
        if (!$user->is_first_login && !$user->force_password_change && $user->status !== 'pending') {
            return redirect()->route('dashboard')
                ->with('info', 'Password change not required.');
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => $this->passwordService->getValidationRules($user->id),
            'password_confirmation' => 'required|string|same:password',
        ], $this->passwordService->getValidationMessages());

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.'
            ])->withInput();
        }

        // Update password using service
        $result = $this->passwordService->updatePassword($user, $request->password);
        
        if (!$result['success']) {
            return back()->withErrors($result['errors'])->withInput();
        }

        // Update user status and flags
        $user->update([
            'is_first_login' => false,
            'force_password_change' => false,
            'status' => 'active',
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays(90), // 90-day expiry
        ]);

        // Add to password history
        PasswordHistory::addPasswordToHistory($user->id, $request->password, [
            'change_reason' => $user->is_first_login ? 'first_login' : 'admin_forced',
            'change_method' => 'form',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'change_notes' => 'Password changed during first login or forced change'
        ]);

        // Log successful password change
        Log::info('First-time password change completed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'change_reason' => $user->is_first_login ? 'first_login' : 'admin_forced'
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Password changed successfully! Welcome to Analytics Hub.');
    }

    /**
     * Show expired password change form
     * 
     * Displays the password change form for users with expired passwords.
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showExpiredForm(Request $request)
    {
        $user = Auth::user();
        
        // Check if password is actually expired
        if (!$this->passwordService->isPasswordExpired($user)) {
            return redirect()->route('dashboard')
                ->with('info', 'Your password has not expired.');
        }

        // Get password expiry information
        $expiryInfo = PasswordHistory::getPasswordExpiry($user->id);
        
        // Log access to expired password form
        Log::info('User accessing expired password change form', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'password_expires_at' => $user->password_expires_at,
            'days_expired' => $expiryInfo['days_expired'] ?? 0
        ]);

        return view('auth.expired-password-change', [
            'user' => $user,
            'expiryInfo' => $expiryInfo
        ]);
    }

    /**
     * Handle expired password change
     * 
     * Processes the password change request for users with expired passwords.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateExpired(Request $request)
    {
        $user = Auth::user();
        
        // Check if password is actually expired
        if (!$this->passwordService->isPasswordExpired($user)) {
            return redirect()->route('dashboard')
                ->with('info', 'Your password has not expired.');
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => $this->passwordService->getValidationRules($user->id),
            'password_confirmation' => 'required|string|same:password',
        ], $this->passwordService->getValidationMessages());

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.'
            ])->withInput();
        }

        // Update password using service
        $result = $this->passwordService->updatePassword($user, $request->password);
        
        if (!$result['success']) {
            return back()->withErrors($result['errors'])->withInput();
        }

        // Update user password expiry
        $user->update([
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays(90), // Reset 90-day expiry
            'force_password_change' => false,
        ]);

        // Add to password history
        PasswordHistory::addPasswordToHistory($user->id, $request->password, [
            'change_reason' => 'policy_expired',
            'change_method' => 'form',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'change_notes' => 'Password changed due to expiry policy'
        ]);

        // Log successful password change
        Log::info('Expired password change completed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_expiry' => now()->addDays(90)
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Password updated successfully! Your password is now valid for another 90 days.');
    }

    /**
     * Get password strength requirements for frontend
     * 
     * Returns password requirements as JSON for dynamic validation.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPasswordRequirements()
    {
        return response()->json([
            'requirements' => [
                'min_length' => 8,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_special' => true,
                'no_reuse_count' => 5
            ],
            'special_characters' => '!@#$%^&*()_+-=[]{}|;:,.<>?'
        ]);
    }
}