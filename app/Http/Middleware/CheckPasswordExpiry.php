<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\PasswordValidationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class CheckPasswordExpiry
 * 
 * Middleware that enforces password expiry policies:
 * - Checks if user's password has expired (90 days)
 * - Redirects to password change page if expired
 * - Logs password expiry checks
 * - Handles first-time login password requirements
 * 
 * @package App\Http\Middleware
 */
class CheckPasswordExpiry
{
    /**
     * Password validation service instance
     * 
     * @var PasswordValidationService
     */
    protected $passwordService;

    /**
     * Password expiry days
     */
    const PASSWORD_EXPIRY_DAYS = 90;

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
        
        // Skip password expiry check for certain routes
        if ($this->shouldSkipCheck($request)) {
            return $next($request);
        }
        
        // Check for first-time login (password change required)
        if ($user->status === 'pending' || $user->force_password_change) {
            $this->logPasswordCheck($request, $user, 'first_time_login_required', 
                'First-time login password change required');
            
            // For AJAX requests, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Password change required',
                    'redirect' => route('password.first-change'),
                    'message' => 'Please change your password to continue.'
                ], 403);
            }
            
            return redirect()->route('password.first-change')
                ->with('message', 'Please change your password to continue.');
        }
        
        // Check password expiry using service
        if ($this->passwordService->isPasswordExpired($user)) {
            $passwordAge = $this->getPasswordAge($user);
            
            $this->logPasswordCheck($request, $user, 'password_expired', 
                "Password has expired. Age: {$passwordAge} days");
            
            // For AJAX requests, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Password expired',
                    'redirect' => route('password.expired'),
                    'message' => 'Your password has expired. Please change it to continue.',
                    'password_age_days' => $passwordAge
                ], 403);
            }
            
            return redirect()->route('password.expired')
                ->with('message', 'Your password has expired. Please change it to continue.')
                ->with('password_age_days', $passwordAge);
        }
        
        // Check if password is nearing expiry (warn at 7 days)
        $passwordAge = $this->getPasswordAge($user);
        if ($passwordAge >= (self::PASSWORD_EXPIRY_DAYS - 7) && $passwordAge < self::PASSWORD_EXPIRY_DAYS) {
            $daysLeft = self::PASSWORD_EXPIRY_DAYS - $passwordAge;
            
            $this->logPasswordCheck($request, $user, 'password_expiry_warning', 
                "Password expires in {$daysLeft} days");
            
            // Add warning to session for display
            $request->session()->flash('password_warning', 
                "Your password will expire in {$daysLeft} day(s). Please change it soon.");
        }
        
        return $next($request);
    }

    /**
     * Determine if password expiry check should be skipped for this route
     * 
     * @param Request $request
     * @return bool
     */
    private function shouldSkipCheck(Request $request): bool
    {
        $skipRoutes = [
            'password.first-change',
            'password.expired',
            'password.change',
            'password.update',
            'logout',
            'terms.accept',
            'terms.show',
            'terms.store'
        ];
        
        $currentRoute = $request->route()->getName();
        
        return in_array($currentRoute, $skipRoutes) || 
               str_starts_with($currentRoute, 'password.');
    }

    /**
     * Get password age in days
     * 
     * @param \App\Models\User $user
     * @return int
     */
    private function getPasswordAge($user): int
    {
        if (!$user->password_changed_at) {
            return self::PASSWORD_EXPIRY_DAYS + 1; // Force expiry if never changed
        }
        
        return Carbon::parse($user->password_changed_at)->diffInDays(Carbon::now());
    }

    /**
     * Log password check activity
     * 
     * @param Request $request
     * @param \App\Models\User $user
     * @param string $action
     * @param string $description
     * @return void
     */
    private function logPasswordCheck(Request $request, $user, string $action, string $description): void
    {
        $passwordAge = $this->getPasswordAge($user);
        
        \App\Models\ActivityLog::create([
            'user_id' => $user->id,
            'subject_type' => \App\Models\User::class,
            'subject_id' => $user->id,
            'event' => $action, // Add required event field
            'action' => $action,
            'description' => $description,
            'properties' => [
                'password_age_days' => $passwordAge,
                'password_changed_at' => $user->password_changed_at?->toISOString(),
                'force_password_change' => $user->force_password_change,
                'user_status' => $user->status,
                'expiry_threshold_days' => self::PASSWORD_EXPIRY_DAYS,
                'is_expired' => $passwordAge >= self::PASSWORD_EXPIRY_DAYS,
                'days_until_expiry' => max(0, self::PASSWORD_EXPIRY_DAYS - $passwordAge),
                'ip_address' => $request->ip(),
                'route' => $request->route()->getName()
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'is_sensitive' => true,
            'severity' => $this->getSeverityLevel($action, $passwordAge),
            'category' => 'password_security'
        ]);
    }

    /**
     * Get severity level based on action and password age
     * 
     * @param string $action
     * @param int $passwordAge
     * @return string
     */
    private function getSeverityLevel(string $action, int $passwordAge): string
    {
        if ($action === 'password_expired' || $action === 'first_time_login_required') {
            return 'high';
        }
        
        if ($action === 'password_expiry_warning' && $passwordAge >= (self::PASSWORD_EXPIRY_DAYS - 3)) {
            return 'medium';
        }
        
        return 'low';
    }
}