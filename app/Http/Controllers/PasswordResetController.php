<?php

// Purpose: Handle password reset functionality with UUID tokens
// Related Feature: Password Management - Forgot Password Flow
// Dependencies: User model, PasswordValidationService, Email system

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasswordValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * PasswordResetController
 * 
 * Handles password reset requests, token validation, and password updates
 * with security measures including rate limiting and token expiry.
 */
class PasswordResetController extends Controller
{
    /**
     * Password reset configuration constants
     */
    const TOKEN_EXPIRY_MINUTES = 120; // 2 hours as per requirements
    const COOLDOWN_SECONDS = 30;     // 30 seconds between requests
    const MAX_ATTEMPTS_PER_HOUR = 5; // Rate limiting
    
    /**
     * Password validation service instance
     */
    protected $passwordService;
    
    /**
     * Constructor
     */
    public function __construct(PasswordValidationService $passwordService)
    {
        $this->passwordService = $passwordService;
    }
    
    /**
     * Show forgot password form
     * 
     * @return \Illuminate\View\View
     */
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }
    
    /**
     * Handle forgot password request
     * 
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function sendResetLink(Request $request)
    {
        // Validate email input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:idbi_users,email',
        ], [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.exists' => 'No account found with this email address.',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            return back()->withErrors($validator)->withInput();
        }
        
        $email = $request->email;
        $ipAddress = $request->ip();
        
        // Check rate limiting
        if (!$this->checkRateLimit($email, $ipAddress)) {
            $message = 'Too many password reset attempts. Please wait before trying again.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 429);
            }
            
            return back()->withErrors(['email' => $message]);
        }
        
        // Check cooldown period
        if (!$this->checkCooldown($email)) {
            $message = 'Please wait ' . self::COOLDOWN_SECONDS . ' seconds before requesting another reset.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 429);
            }
            
            return back()->withErrors(['email' => $message]);
        }
        
        try {
            // Find user
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                // For security, don't reveal if email exists
                $message = 'If an account with this email exists, a password reset link has been sent.';
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                    ]);
                }
                
                return back()->with('status', $message);
            }
            
            // Generate UUID token
            $token = Str::uuid()->toString();
            
            // Store reset token in database
            DB::table('idbi_password_resets')->updateOrInsert(
                ['email' => $email],
                [
                    'email' => $email,
                    'token' => Hash::make($token),
                    'created_at' => now(),
                    'expires_at' => now()->addMinutes(self::TOKEN_EXPIRY_MINUTES),
                    'ip_address' => $ipAddress,
                    'user_agent' => $request->userAgent(),
                    'used' => false,
                ]
            );
            
            // Send reset email
            $this->sendResetEmail($user, $token);
            
            // Log the attempt
            $this->logResetAttempt($email, $ipAddress, 'sent');
            
            $message = 'Password reset link has been sent to your email address.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }
            
            return back()->with('status', $message);
            
        } catch (\Exception $e) {
            \Log::error('Password reset error: ' . $e->getMessage());
            
            $message = 'An error occurred while processing your request. Please try again.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 500);
            }
            
            return back()->withErrors(['email' => $message]);
        }
    }
    
    /**
     * Show password reset form
     * 
     * @param Request $request HTTP request
     * @param string $token Reset token
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showResetForm(Request $request, string $token)
    {
        // Validate token format
        if (!Str::isUuid($token)) {
            return redirect()->route('login')->withErrors(['token' => 'Invalid reset token.']);
        }
        
        // Check if token exists and is valid
        $resetRecord = $this->findValidResetRecord($token);
        
        if (!$resetRecord) {
            return redirect()->route('login')->withErrors(['token' => 'Invalid or expired reset token.']);
        }
        
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $resetRecord->email,
        ]);
    }
    
    /**
     * Handle password reset
     * 
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function resetPassword(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'token' => 'required|uuid',
            'email' => 'required|email',
            'password' => $this->passwordService->getValidationRules(),
            'password_confirmation' => 'required|same:password',
        ], $this->passwordService->getValidationMessages());
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            // Find and validate reset record
            $resetRecord = $this->findValidResetRecord($request->token);
            
            if (!$resetRecord || $resetRecord->email !== $request->email) {
                $message = 'Invalid or expired reset token.';
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 400);
                }
                
                return back()->withErrors(['token' => $message]);
            }
            
            // Find user
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                $message = 'User not found.';
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 404);
                }
                
                return back()->withErrors(['email' => $message]);
            }
            
            // Change password using password service
            $result = $this->passwordService->changePassword($user, $request->password, [
                'reason' => 'forgot_password',
                'method' => 'reset',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            if (!$result['success']) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $result['errors'],
                    ], 422);
                }
                
                return back()->withErrors($result['errors']);
            }
            
            // Mark token as used
            DB::table('idbi_password_resets')
                ->where('email', $request->email)
                ->update(['used' => true, 'used_at' => now()]);
            
            // Log successful reset
            $this->logResetAttempt($request->email, $request->ip(), 'completed');
            
            $message = 'Password has been reset successfully. You can now log in with your new password.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }
            
            return redirect()->route('login')->with('status', $message);
            
        } catch (\Exception $e) {
            \Log::error('Password reset completion error: ' . $e->getMessage());
            
            $message = 'An error occurred while resetting your password. Please try again.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 500);
            }
            
            return back()->withErrors(['password' => $message]);
        }
    }
    
    /**
     * Check rate limiting for password reset requests
     * 
     * @param string $email Email address
     * @param string $ipAddress IP address
     * @return bool True if within rate limits
     */
    protected function checkRateLimit(string $email, string $ipAddress): bool
    {
        $hourAgo = now()->subHour();
        
        // Check attempts by email
        $emailAttempts = DB::table('idbi_password_resets')
            ->where('email', $email)
            ->where('created_at', '>=', $hourAgo)
            ->count();
        
        // Check attempts by IP
        $ipAttempts = DB::table('idbi_password_resets')
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', $hourAgo)
            ->count();
        
        return $emailAttempts < self::MAX_ATTEMPTS_PER_HOUR && $ipAttempts < self::MAX_ATTEMPTS_PER_HOUR;
    }
    
    /**
     * Check cooldown period between requests
     * 
     * @param string $email Email address
     * @return bool True if cooldown period has passed
     */
    protected function checkCooldown(string $email): bool
    {
        $lastRequest = DB::table('idbi_password_resets')
            ->where('email', $email)
            ->orderBy('created_at', 'desc')
            ->first();
        
        if (!$lastRequest) {
            return true;
        }
        
        $cooldownExpiry = Carbon::parse($lastRequest->created_at)->addSeconds(self::COOLDOWN_SECONDS);
        
        return now()->isAfter($cooldownExpiry);
    }
    
    /**
     * Find valid reset record by token
     * 
     * @param string $token Reset token
     * @return object|null Reset record if valid
     */
    protected function findValidResetRecord(string $token): ?object
    {
        $records = DB::table('idbi_password_resets')
            ->where('expires_at', '>', now())
            ->where('used', false)
            ->get();
        
        foreach ($records as $record) {
            if (Hash::check($token, $record->token)) {
                return $record;
            }
        }
        
        return null;
    }
    
    /**
     * Send password reset email
     * 
     * @param User $user User instance
     * @param string $token Reset token
     * @return void
     */
    protected function sendResetEmail(User $user, string $token): void
    {
        $resetUrl = route('password.reset', ['token' => $token]);
        
        // TODO: Implement proper email template system
        // For now, using basic Laravel mail
        Mail::send('emails.password-reset', [
            'user' => $user,
            'resetUrl' => $resetUrl,
            'expiryMinutes' => self::TOKEN_EXPIRY_MINUTES,
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                   ->subject('Password Reset Request - Analytics Hub');
        });
    }
    
    /**
     * Log password reset attempt
     * 
     * @param string $email Email address
     * @param string $ipAddress IP address
     * @param string $status Attempt status
     * @return void
     */
    protected function logResetAttempt(string $email, string $ipAddress, string $status): void
    {
        // TODO: Implement activity logging
        \Log::info('Password reset attempt', [
            'email' => $email,
            'ip_address' => $ipAddress,
            'status' => $status,
            'timestamp' => now(),
        ]);
    }
}