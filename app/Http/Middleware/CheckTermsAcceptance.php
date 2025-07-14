<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\TermsNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Class CheckTermsAcceptance
 * 
 * Middleware that enforces Terms & Conditions acceptance:
 * - Checks if user has accepted current T&C version
 * - Redirects to T&C acceptance page if required
 * - Logs T&C compliance checks
 * - Handles T&C version updates
 * 
 * @package App\Http\Middleware
 */
class CheckTermsAcceptance
{
    /**
     * Terms notification service instance
     * 
     * @var TermsNotificationService
     */
    protected $termsService;

    /**
     * Constructor
     * 
     * @param TermsNotificationService $termsService
     */
    public function __construct(TermsNotificationService $termsService)
    {
        $this->termsService = $termsService;
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
        
        // Skip T&C check for certain routes
        if ($this->shouldSkipCheck($request)) {
            return $next($request);
        }
        
        // Check if Terms & Conditions acceptance is required
        if ($this->termsService->userNeedsReacceptance($user)) {
            $currentVersion = $this->termsService->getCurrentVersion();
            $userVersion = $user->terms_version_accepted ?? 'none';
            
            $this->logTermsCheck($request, $user, 'terms_reacceptance_required', 
                "Terms & Conditions re-acceptance required. Current: {$currentVersion}, User: {$userVersion}");
            
            // For AJAX requests, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Terms & Conditions acceptance required',
                    'redirect' => route('terms.accept'),
                    'message' => 'Our Terms & Conditions have been updated. Please review and accept the new terms to continue.'
                ], 403);
            }
            
            return redirect()->route('terms.accept')
                ->with('message', 'Our Terms & Conditions have been updated. Please review and accept the new terms to continue.');
        }
        
        // Log successful T&C compliance check
        $this->logTermsCheck($request, $user, 'terms_compliance_verified', 
            'User has accepted current Terms & Conditions version');
        
        return $next($request);
    }

    /**
     * Determine if T&C check should be skipped for this route
     * 
     * @param Request $request
     * @return bool
     */
    private function shouldSkipCheck(Request $request): bool
    {
        $skipRoutes = [
            'terms.accept',
            'terms.show',
            'terms.store',
            'logout',
            'password.first-change',
            'password.expired',
            'verification.notice',
            'verification.verify',
            'verification.send'
        ];
        
        $currentRoute = $request->route()->getName();
        
        return in_array($currentRoute, $skipRoutes) || 
               str_starts_with($currentRoute, 'password.') ||
               str_starts_with($currentRoute, 'verification.');
    }

    /**
     * Log Terms & Conditions check activity
     * 
     * @param Request $request
     * @param \App\Models\User $user
     * @param string $action
     * @param string $description
     * @return void
     */
    private function logTermsCheck(Request $request, $user, string $action, string $description): void
    {
        $currentVersion = $this->termsService->getCurrentVersion();
        $userVersion = $user->terms_version_accepted ?? 'none';
        
        \App\Models\ActivityLog::create([
            'user_id' => $user->id,
            'subject_type' => \App\Models\User::class,
            'subject_id' => $user->id,
            'event' => $action, // Add required event field
            'action' => $action,
            'description' => $description,
            'properties' => [
                'current_terms_version' => $currentVersion,
                'user_terms_version' => $userVersion,
                'terms_accepted_at' => $user->terms_accepted_at?->toISOString(),
                'needs_reacceptance' => $this->termsService->userNeedsReacceptance($user),
                'ip_address' => $request->ip(),
                'route' => $request->route()->getName(),
                'user_agent' => $request->userAgent()
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'is_sensitive' => false,
            'severity' => $action === 'terms_reacceptance_required' ? 'medium' : 'low',
            'category' => 'terms_compliance'
        ]);
    }
}