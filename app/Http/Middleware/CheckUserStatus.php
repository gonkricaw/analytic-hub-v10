<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\PasswordValidationService;
use Carbon\Carbon;

/**
 * Class CheckUserStatus
 * 
 * Middleware that validates user status and enforces business rules:
 * - Checks if user account is active
 * - Validates user is not suspended
 * - Enforces password expiry policies
 * - Handles first-time login requirements
 * 
 * @package App\Http\Middleware
 */
class CheckUserStatus
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
    }
    /**
     * Password expiry days
     */
    const PASSWORD_EXPIRY_DAYS = 90;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check if user account is active
        if (!$user->is_active) {
            $this->logStatusCheck($request, $user, 'inactive_account', 'Account is inactive');
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Your account has been deactivated. Please contact administrator.');
        }
        
        // Check if user is suspended
        if ($user->status === 'suspended') {
            $this->logStatusCheck($request, $user, 'suspended_account', 'Account is suspended');
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Your account has been suspended. Please contact administrator.');
        }
        
        // Check if email is verified
        if (!$user->email_verified_at) {
            $this->logStatusCheck($request, $user, 'unverified_email', 'Email not verified');
            return redirect()->route('verification.notice')
                ->with('message', 'Please verify your email address to continue.');
        }
        
        // Check for first-time login (password change required)
        if ($user->status === 'pending' || $user->force_password_change) {
            $this->logStatusCheck($request, $user, 'first_time_login', 'First-time login detected');
            return redirect()->route('password.first-change')
                ->with('message', 'Please change your password to continue.');
        }
        
        // Check password expiry using service
        if ($this->passwordService->isPasswordExpired($user)) {
            $this->logStatusCheck($request, $user, 'password_expired', 'Password has expired');
            return redirect()->route('password.expired')
                ->with('message', 'Your password has expired. Please change it to continue.');
        }
        
        // Check if Terms & Conditions acceptance is required
        if (!$user->terms_accepted_at) {
            $this->logStatusCheck($request, $user, 'terms_not_accepted', 'Terms & Conditions not accepted');
            return redirect()->route('terms.accept')
                ->with('message', 'Please accept the Terms & Conditions to continue.');
        }
        
        // Update last seen timestamp
        $user->update([
            'last_seen_at' => Carbon::now(),
            'last_ip' => $request->ip()
        ]);
        
        return $next($request);
    }

    /**
     * Check if user's password has expired
     * 
     * @param \App\Models\User $user
     * @return bool
     */
    private function isPasswordExpired($user): bool
    {
        if (!$user->password_changed_at) {
            return true; // Force password change if never changed
        }
        
        $passwordAge = Carbon::parse($user->password_changed_at)->diffInDays(Carbon::now());
        return $passwordAge >= self::PASSWORD_EXPIRY_DAYS;
    }

    /**
     * Log status check activity
     * 
     * @param Request $request
     * @param \App\Models\User $user
     * @param string $action
     * @param string $description
     * @return void
     */
    private function logStatusCheck(Request $request, $user, string $action, string $description): void
    {
        \App\Models\ActivityLog::create([
            'user_id' => $user->id,
            'subject_type' => \App\Models\User::class,
            'subject_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'properties' => [
                'user_status' => $user->status,
                'is_active' => $user->is_active,
                'email_verified' => !is_null($user->email_verified_at),
                'terms_accepted' => !is_null($user->terms_accepted_at),
                'password_age_days' => $user->password_changed_at ? 
                    Carbon::parse($user->password_changed_at)->diffInDays(Carbon::now()) : null,
                'ip_address' => $request->ip(),
                'route' => $request->route()->getName()
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'is_sensitive' => true,
            'severity' => $this->getSeverityLevel($action),
            'category' => 'user_status'
        ]);
    }

    /**
     * Get severity level based on action
     * 
     * @param string $action
     * @return string
     */
    private function getSeverityLevel(string $action): string
    {
        $highSeverityActions = ['suspended_account', 'inactive_account'];
        $mediumSeverityActions = ['password_expired', 'unverified_email'];
        
        if (in_array($action, $highSeverityActions)) {
            return 'high';
        } elseif (in_array($action, $mediumSeverityActions)) {
            return 'medium';
        }
        
        return 'low';
    }
}