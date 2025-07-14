<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

/**
 * Class AuthenticateUser
 * 
 * Custom authentication middleware that handles:
 * - User authentication verification
 * - Session timeout management
 * - Activity tracking
 * - Automatic logout on idle
 * 
 * @package App\Http\Middleware
 */
class AuthenticateUser
{
    /**
     * Session timeout in minutes
     */
    const SESSION_TIMEOUT = 30;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('message', 'Please log in to access this page.');
        }
        
        // Check session timeout
        if ($this->isSessionExpired($request)) {
            $this->logoutUser($request, 'Session expired due to inactivity');
            return redirect()->route('login')
                ->with('message', 'Your session has expired. Please log in again.');
        }
        
        // Update last activity
        $request->session()->put('last_activity', time());
        
        return $next($request);
    }

    /**
     * Check if session has expired
     * 
     * @param Request $request
     * @return bool
     */
    private function isSessionExpired(Request $request): bool
    {
        $lastActivity = $request->session()->get('last_activity');
        
        if (!$lastActivity) {
            return true;
        }
        
        $timeoutSeconds = self::SESSION_TIMEOUT * 60;
        return (time() - $lastActivity) > $timeoutSeconds;
    }

    /**
     * Logout user and clean session
     * 
     * @param Request $request
     * @param string $reason
     * @return void
     */
    private function logoutUser(Request $request, string $reason): void
    {
        $user = Auth::user();
        
        if ($user) {
            // Log the automatic logout
            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'subject_type' => \App\Models\User::class,
                'subject_id' => $user->id,
                'event' => 'auto_logout', // Add required event field
                'action' => 'auto_logout',
                'description' => "User automatically logged out: {$reason}",
                'properties' => [
                    'reason' => $reason,
                    'ip_address' => $request->ip(),
                    'last_activity' => $request->session()->get('last_activity'),
                    'session_duration' => $this->getSessionDuration($request)
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'is_sensitive' => true,
                'severity' => 'medium',
                'category' => 'authentication'
            ]);
        }
        
        // Clear authentication and session
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Get session duration in minutes
     * 
     * @param Request $request
     * @return int
     */
    private function getSessionDuration(Request $request): int
    {
        $lastActivity = $request->session()->get('last_activity', time());
        return (int) ((time() - $lastActivity) / 60);
    }
}