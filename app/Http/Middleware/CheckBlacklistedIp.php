<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\BlacklistedIp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CheckBlacklistedIp
 * 
 * Middleware that checks if the requesting IP address is blacklisted
 * and blocks access if found in the blacklist.
 * 
 * Features:
 * - IP blacklist validation
 * - Automatic blocking of blacklisted IPs
 * - Activity logging for blocked attempts
 * - Support for temporary and permanent blocks
 * 
 * @package App\Http\Middleware
 */
class CheckBlacklistedIp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the blacklisted_ips table exists before querying
        if (!\Illuminate\Support\Facades\Schema::hasTable('idbi_blacklisted_ips')) {
            return $next($request);
        }
        
        $ipAddress = $request->ip();
        
        // Check if IP is blacklisted
        $blacklistedIp = BlacklistedIp::where('ip_address', $ipAddress)
            ->where('is_active', true)
            ->first();
        
        if ($blacklistedIp) {
            // Check if it's a temporary block that has expired
            if ($blacklistedIp->expires_at && Carbon::now()->gt($blacklistedIp->expires_at)) {
                // Remove expired temporary block
                $blacklistedIp->update(['is_active' => false]);
                
                $this->logIpActivity($request, 'blacklist_expired', 
                    'Temporary IP blacklist has expired and been removed', $blacklistedIp);
            } else {
                // IP is still blacklisted - block access
                $this->logBlockedAttempt($request, $blacklistedIp);
                
                return response()->view('errors.blocked', [
                    'message' => 'Access denied. Your IP address has been blocked due to security violations.',
                    'ip' => $ipAddress,
                    'blocked_at' => $blacklistedIp->created_at,
                    'reason' => $blacklistedIp->reason,
                    'expires_at' => $blacklistedIp->expires_at
                ], 403);
            }
        }
        
        return $next($request);
    }

    /**
     * Log blocked access attempt
     * 
     * @param Request $request
     * @param BlacklistedIp $blacklistedIp
     * @return void
     */
    private function logBlockedAttempt(Request $request, BlacklistedIp $blacklistedIp): void
    {
        \App\Models\ActivityLog::create([
            'user_id' => null, // No user since access is blocked
            'subject_type' => BlacklistedIp::class,
            'subject_id' => $blacklistedIp->id,
            'action' => 'blocked_access_attempt',
            'description' => "Blocked access attempt from blacklisted IP: {$request->ip()}",
            'properties' => [
                'ip_address' => $request->ip(),
                'blacklist_reason' => $blacklistedIp->reason,
                'blacklist_type' => $blacklistedIp->expires_at ? 'temporary' : 'permanent',
                'blocked_at' => $blacklistedIp->created_at,
                'expires_at' => $blacklistedIp->expires_at,
                'attempt_count' => $blacklistedIp->increment('attempt_count'),
                'requested_url' => $request->fullUrl(),
                'referer' => $request->header('referer'),
                'country' => $this->getCountryFromIp($request->ip())
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'is_sensitive' => true,
            'severity' => 'high',
            'category' => 'security'
        ]);
        
        // Update blacklisted IP record with attempt count
        $blacklistedIp->increment('attempt_count');
        $blacklistedIp->update(['last_attempt_at' => Carbon::now()]);
    }

    /**
     * Log IP-related activity
     * 
     * @param Request $request
     * @param string $action
     * @param string $description
     * @param BlacklistedIp|null $blacklistedIp
     * @return void
     */
    private function logIpActivity(Request $request, string $action, string $description, $blacklistedIp = null): void
    {
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'subject_type' => BlacklistedIp::class,
            'subject_id' => $blacklistedIp ? $blacklistedIp->id : null,
            'action' => $action,
            'description' => $description,
            'properties' => [
                'ip_address' => $request->ip(),
                'blacklist_data' => $blacklistedIp ? [
                    'reason' => $blacklistedIp->reason,
                    'type' => $blacklistedIp->expires_at ? 'temporary' : 'permanent',
                    'created_at' => $blacklistedIp->created_at,
                    'expires_at' => $blacklistedIp->expires_at,
                    'attempt_count' => $blacklistedIp->attempt_count
                ] : null
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'is_sensitive' => true,
            'severity' => 'medium',
            'category' => 'security'
        ]);
    }

    /**
     * Get country from IP address (placeholder for future implementation)
     * 
     * @param string $ip
     * @return string|null
     */
    private function getCountryFromIp(string $ip): ?string
    {
        // TODO: Implement IP geolocation service
        // This could use services like MaxMind GeoIP, ipapi.co, etc.
        return null;
    }
}