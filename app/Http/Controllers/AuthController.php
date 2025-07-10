<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LoginAttempt;
use App\Models\BlacklistedIp;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Class AuthController
 * 
 * Handles user authentication with comprehensive security features including:
 * - IP tracking and blacklisting
 * - Failed login attempt monitoring
 * - Session management with timeout
 * - Remember me functionality
 * - Activity logging
 * 
 * @package App\Http\Controllers
 */
class AuthController extends Controller
{
    /**
     * Maximum failed login attempts before IP blacklisting
     */
    const MAX_FAILED_ATTEMPTS = 30;
    
    /**
     * Session timeout in minutes
     */
    const SESSION_TIMEOUT = 30;
    
    /**
     * Remember me token expiry in days
     */
    const REMEMBER_TOKEN_EXPIRY = 30;

    /**
     * Show the login form
     * 
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showLoginForm(Request $request)
    {
        // Check if user is already authenticated
        if (Auth::check()) {
            return redirect()->intended('/dashboard');
        }
        
        // Check if IP is blacklisted
        if ($this->isIpBlacklisted($request->ip())) {
            return view('auth.blocked', [
                'message' => 'Your IP address has been temporarily blocked due to multiple failed login attempts. Please contact the administrator.'
            ]);
        }
        
        return view('auth.login');
    }

    /**
     * Handle login attempt
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // Check if IP is blacklisted
        if ($this->isIpBlacklisted($request->ip())) {
            return back()->withErrors([
                'email' => 'Your IP address has been blocked. Please contact the administrator.'
            ]);
        }
        
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->except('password'));
        }
        
        $email = $request->input('email');
        $password = $request->input('password');
        $remember = $request->boolean('remember');
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        
        // Find user by email
        $user = User::where('email', $email)->first();
        
        // Log login attempt
        $this->logLoginAttempt($email, $ipAddress, $userAgent, false);
        
        // Check if user exists and credentials are valid
        if (!$user || !Hash::check($password, $user->password)) {
            $this->handleFailedLogin($email, $ipAddress, $userAgent);
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.'
            ])->withInput($request->except('password'));
        }
        
        // Check user status
        if ($user->status !== 'active') {
            $this->logActivity($user, 'login_blocked', [
                'reason' => 'User status: ' . $user->status,
                'ip_address' => $ipAddress
            ]);
            
            return back()->withErrors([
                'email' => 'Your account is currently suspended. Please contact the administrator.'
            ])->withInput($request->except('password'));
        }
        
        // Check if account is locked
        if ($user->locked_until && Carbon::now()->lt($user->locked_until)) {
            return back()->withErrors([
                'email' => 'Your account is temporarily locked. Please try again later.'
            ])->withInput($request->except('password'));
        }
        
        // Successful login - reset failed attempts
        $this->resetFailedAttempts($ipAddress);
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => Carbon::now(),
            'last_login_ip' => $ipAddress
        ]);
        
        // Log successful login attempt
        $this->logLoginAttempt($email, $ipAddress, $userAgent, true);
        
        // Authenticate user
        Auth::login($user, $remember);
        
        // Set session timeout
        $request->session()->put('last_activity', time());
        
        // Log activity
        $this->logActivity($user, 'login_success', [
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'remember_me' => $remember
        ]);
        
        // Check if first login or password expired
        if ($user->is_first_login || $this->isPasswordExpired($user)) {
            return redirect()->route('password.change.form');
        }
        
        // Check if T&C accepted
        if (!$user->terms_accepted) {
            return redirect()->route('terms.show');
        }
        
        return redirect()->intended('/dashboard');
    }

    /**
     * Handle logout
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            // Log activity
            $this->logActivity($user, 'logout', [
                'ip_address' => $request->ip(),
                'session_duration' => $this->getSessionDuration()
            ]);
        }
        
        // Clear session data
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login')->with('message', 'You have been successfully logged out.');
    }

    /**
     * Check if IP address is blacklisted
     * 
     * @param string $ipAddress
     * @return bool
     */
    private function isIpBlacklisted(string $ipAddress): bool
    {
        return BlacklistedIp::where('ip_address', $ipAddress)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->exists();
    }

    /**
     * Handle failed login attempt
     * 
     * @param string $email
     * @param string $ipAddress
     * @param string $userAgent
     * @return void
     */
    private function handleFailedLogin(string $email, string $ipAddress, string $userAgent): void
    {
        // Count failed attempts for this IP in the last hour
        $failedAttempts = LoginAttempt::where('ip_address', $ipAddress)
            ->where('success', false)
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->count();
        
        // If threshold exceeded, blacklist IP
        if ($failedAttempts >= self::MAX_FAILED_ATTEMPTS - 1) {
            $this->blacklistIp($ipAddress, $userAgent, $email);
        }
        
        // Update user failed attempts if user exists
        $user = User::where('email', $email)->first();
        if ($user) {
            $user->increment('failed_login_attempts');
            
            // Lock account after 5 failed attempts
            if ($user->failed_login_attempts >= 5) {
                $user->update([
                    'locked_until' => Carbon::now()->addMinutes(15)
                ]);
            }
        }
    }

    /**
     * Blacklist IP address
     * 
     * @param string $ipAddress
     * @param string $userAgent
     * @param string $email
     * @return void
     */
    private function blacklistIp(string $ipAddress, string $userAgent, string $email): void
    {
        BlacklistedIp::create([
            'ip_address' => $ipAddress,
            'reason' => 'Exceeded maximum failed login attempts (' . self::MAX_FAILED_ATTEMPTS . ')',
            'blocked_by' => 'system',
            'blocked_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addHours(24), // 24-hour block
            'status' => 'active',
            'user_agent' => $userAgent,
            'attempted_email' => $email,
            'attempt_count' => self::MAX_FAILED_ATTEMPTS
        ]);
        
        // Log security event
        ActivityLog::create([
            'user_id' => null,
            'subject_type' => BlacklistedIp::class,
            'subject_id' => null,
            'action' => 'ip_blacklisted',
            'description' => "IP address {$ipAddress} blacklisted for exceeding failed login attempts",
            'properties' => [
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'attempted_email' => $email,
                'failed_attempts' => self::MAX_FAILED_ATTEMPTS
            ],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'is_sensitive' => true,
            'severity' => 'high',
            'category' => 'security'
        ]);
    }

    /**
     * Reset failed attempts for IP
     * 
     * @param string $ipAddress
     * @return void
     */
    private function resetFailedAttempts(string $ipAddress): void
    {
        // Remove any active blacklist for this IP
        BlacklistedIp::where('ip_address', $ipAddress)
            ->where('status', 'active')
            ->update(['status' => 'inactive']);
    }

    /**
     * Log login attempt
     * 
     * @param string $email
     * @param string $ipAddress
     * @param string $userAgent
     * @param bool $success
     * @return void
     */
    private function logLoginAttempt(string $email, string $ipAddress, string $userAgent, bool $success): void
    {
        LoginAttempt::create([
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'success' => $success,
            'attempted_at' => Carbon::now()
        ]);
    }

    /**
     * Log user activity
     * 
     * @param User $user
     * @param string $action
     * @param array $properties
     * @return void
     */
    private function logActivity(User $user, string $action, array $properties = []): void
    {
        ActivityLog::create([
            'user_id' => $user->id,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'action' => $action,
            'description' => "User {$action}: {$user->email}",
            'properties' => $properties,
            'ip_address' => $properties['ip_address'] ?? request()->ip(),
            'user_agent' => $properties['user_agent'] ?? request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'is_sensitive' => in_array($action, ['login_success', 'login_blocked', 'logout']),
            'severity' => $this->getActionSeverity($action),
            'category' => 'authentication'
        ]);
    }

    /**
     * Check if password is expired
     * 
     * @param User $user
     * @return bool
     */
    private function isPasswordExpired(User $user): bool
    {
        if (!$user->password_changed_at) {
            return true; // Force change if never changed
        }
        
        return Carbon::parse($user->password_changed_at)
            ->addDays(90) // 90 days expiry
            ->isPast();
    }

    /**
     * Get session duration in minutes
     * 
     * @return int
     */
    private function getSessionDuration(): int
    {
        $lastActivity = session('last_activity', time());
        return (int) ((time() - $lastActivity) / 60);
    }

    /**
     * Get action severity level
     * 
     * @param string $action
     * @return string
     */
    private function getActionSeverity(string $action): string
    {
        return match ($action) {
            'login_blocked' => 'high',
            'login_success', 'logout' => 'medium',
            default => 'low'
        };
    }
}