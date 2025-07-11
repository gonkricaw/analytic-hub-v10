<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Services\ContentEncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Response;

/**
 * Class ContentViewController
 * 
 * Handles public content viewing with security features.
 * Manages encrypted URLs, access tokens, and secure iframe rendering.
 * Implements browser inspection protection and access logging.
 * 
 * @package App\Http\Controllers
 */
class ContentViewController extends Controller
{
    /**
     * Content encryption service instance
     * 
     * @var ContentEncryptionService
     */
    protected $encryptionService;

    /**
     * Constructor
     * 
     * @param ContentEncryptionService $encryptionService
     */
    public function __construct(ContentEncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
        
        // Apply authentication middleware
        $this->middleware('auth.user');
        $this->middleware('check.status');
    }

    /**
     * Display content by slug.
     * 
     * Shows published content with proper permission checks.
     * Handles both custom HTML and embedded content types.
     * 
     * @param Request $request
     * @param string $slug Content slug
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(Request $request, string $slug)
    {
        try {
            // Find content by slug
            $content = Content::where('slug', $slug)
                ->where('status', 'published')
                ->where(function ($query) {
                    $query->whereNull('published_at')
                          ->orWhere('published_at', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->first();

            if (!$content) {
                abort(404, 'Content not found or not available');
            }

            // Check user permissions
            if (!$this->checkContentPermissions($content)) {
                abort(403, 'You do not have permission to view this content');
            }

            // Log content access
            $this->encryptionService->logContentAccess(
                $content->id,
                'view',
                [
                    'slug' => $slug,
                    'type' => $content->type,
                    'user_agent' => $request->userAgent()
                ]
            );

            // Increment view count
            $content->increment('view_count');
            $content->touch('last_viewed_at');

            // Handle different content types
            if ($content->type === 'embedded') {
                return $this->handleEmbeddedContent($content);
            }

            // Handle custom HTML content
            return $this->handleCustomContent($content);

        } catch (\Exception $e) {
            Log::error('Content viewing failed', [
                'slug' => $slug,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            abort(500, 'Unable to load content');
        }
    }

    /**
     * Display embedded content with UUID masking.
     * 
     * Handles secure embedded content viewing with encrypted URLs
     * and browser inspection protection.
     * 
     * @param Request $request
     * @param string $uuid Content UUID
     * @return \Illuminate\View\View
     */
    public function embed(Request $request, string $uuid)
    {
        try {
            // Find content by UUID
            $content = Content::where('uuid', $uuid)
                ->where('type', 'embedded')
                ->where('status', 'published')
                ->where(function ($query) {
                    $query->whereNull('published_at')
                          ->orWhere('published_at', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->first();

            if (!$content) {
                abort(404, 'Embedded content not found');
            }

            // Check permissions
            if (!$this->checkContentPermissions($content)) {
                abort(403, 'Access denied');
            }

            // Log access
            $this->encryptionService->logContentAccess(
                $content->id,
                'embed_view',
                [
                    'uuid' => $uuid,
                    'referrer' => $request->header('referer')
                ]
            );

            // Generate access token for secure viewing
            $tokenData = $this->encryptionService->generateOneTimeToken($content->id, 30);

            return view('content.embed', [
                'content' => $content,
                'uuid' => $uuid,
                'access_token' => $tokenData['token'],
                'expires_in' => $tokenData['expires_in_seconds'],
                'protection_script' => $this->encryptionService->generateInspectionProtection()
            ]);

        } catch (\Exception $e) {
            Log::error('Embedded content viewing failed', [
                'uuid' => $uuid,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            abort(500, 'Unable to load embedded content');
        }
    }

    /**
     * Generate access token for secure content viewing.
     * 
     * Creates one-time tokens for accessing encrypted embedded content.
     * Validates permissions before token generation.
     * 
     * @param Request $request
     * @param string $uuid Content UUID
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateAccessToken(Request $request, string $uuid)
    {
        try {
            // Find content
            $content = Content::where('uuid', $uuid)
                ->where('type', 'embedded')
                ->where('status', 'published')
                ->first();

            if (!$content) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content not found'
                ], 404);
            }

            // Check permissions
            if (!$this->checkContentPermissions($content)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            // Generate token
            $tokenData = $this->encryptionService->generateOneTimeToken($content->id, 30);

            // Log token generation
            $this->encryptionService->logContentAccess(
                $content->id,
                'token_generated',
                ['uuid' => $uuid]
            );

            return response()->json([
                'success' => true,
                'token' => $tokenData['token'],
                'expires_in' => $tokenData['expires_in_seconds'],
                'expires_at' => $tokenData['expires_at']->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Access token generation failed', [
                'uuid' => $uuid,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token generation failed'
            ], 500);
        }
    }

    /**
     * Secure content viewing with token validation.
     * 
     * Validates one-time tokens and serves decrypted embedded content
     * in a secure iframe with browser protection.
     * 
     * @param Request $request
     * @param string $token Encrypted access token
     * @return \Illuminate\Http\Response
     */
    public function secureView(Request $request, string $token)
    {
        try {
            // Validate token
            $tokenData = $this->encryptionService->validateOneTimeToken($token);
            
            if (!$tokenData) {
                return $this->renderSecurityError('Invalid or expired access token');
            }

            // Find content
            $content = Content::find($tokenData['content_id']);
            
            if (!$content || $content->type !== 'embedded') {
                return $this->renderSecurityError('Content not found or invalid type');
            }

            // Check if content is still accessible
            if ($content->status !== 'published' || 
                ($content->expires_at && $content->expires_at->isPast())) {
                return $this->renderSecurityError('Content is no longer available');
            }

            // Decrypt the embedded URL
            $decryptedUrl = $this->encryptionService->decryptUrl($content->content);

            // Validate domain
            if (!$this->encryptionService->isAllowedDomain($decryptedUrl)) {
                Log::warning('Attempted access to disallowed domain', [
                    'content_id' => $content->id,
                    'url_hash' => hash('sha256', $decryptedUrl),
                    'user_id' => auth()->id()
                ]);
                
                return $this->renderSecurityError('Content source not allowed');
            }

            // Log secure access
            $this->encryptionService->logContentAccess(
                $content->id,
                'secure_view',
                [
                    'token_used' => true,
                    'url_domain' => parse_url($decryptedUrl, PHP_URL_HOST)
                ]
            );

            // Increment view count
            $content->increment('view_count');

            // Generate secure iframe HTML
            $iframeHtml = $this->generateSecureIframe($decryptedUrl, $content);

            return response($iframeHtml)
                ->header('Content-Type', 'text/html')
                ->header('X-Frame-Options', 'SAMEORIGIN')
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('X-XSS-Protection', '1; mode=block')
                ->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        } catch (\Exception $e) {
            Log::error('Secure content viewing failed', [
                'token_length' => strlen($token),
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return $this->renderSecurityError('Security validation failed');
        }
    }

    /**
     * Handle custom HTML content display.
     * 
     * Renders custom HTML content with proper sanitization
     * and security headers.
     * 
     * @param Content $content
     * @return \Illuminate\View\View
     */
    protected function handleCustomContent(Content $content)
    {
        return view('content.show', [
            'content' => $content,
            'meta_title' => $content->meta_title ?: $content->title,
            'meta_description' => $content->meta_description ?: $content->excerpt,
            'meta_keywords' => $content->meta_keywords,
            'canonical_url' => route('content.show', $content->slug)
        ]);
    }

    /**
     * Handle embedded content display.
     * 
     * Redirects to UUID-based embed route for security.
     * 
     * @param Content $content
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleEmbeddedContent(Content $content)
    {
        // Redirect to UUID-based embed route for security
        return redirect()->route('content.embed', $content->uuid);
    }

    /**
     * Check if user has permission to view content.
     * 
     * Validates user roles and content access permissions.
     * 
     * @param Content $content
     * @return bool
     */
    protected function checkContentPermissions(Content $content): bool
    {
        $user = auth()->user();
        
        // Check if content has specific access permissions
        if ($content->access_permissions) {
            $permissions = json_decode($content->access_permissions, true);
            
            // Check role-based access
            if (isset($permissions['roles']) && !empty($permissions['roles'])) {
                $userRoles = $user->roles->pluck('name')->toArray();
                if (!array_intersect($userRoles, $permissions['roles'])) {
                    return false;
                }
            }
            
            // Check user-specific access
            if (isset($permissions['users']) && !empty($permissions['users'])) {
                if (!in_array($user->id, $permissions['users'])) {
                    return false;
                }
            }
        }
        
        // Check visibility settings
        if ($content->visibility_settings) {
            $visibility = json_decode($content->visibility_settings, true);
            
            if (isset($visibility['private']) && $visibility['private'] === true) {
                // Private content - only creator and admins can view
                if ($content->created_by !== $user->id && !$user->hasRole(['admin', 'super_admin'])) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Generate secure iframe HTML for embedded content.
     * 
     * Creates iframe with security attributes and browser protection.
     * 
     * @param string $url Decrypted URL
     * @param Content $content
     * @return string
     */
    protected function generateSecureIframe(string $url, Content $content): string
    {
        $protectionScript = $this->encryptionService->generateInspectionProtection();
        
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($content->title) . '</title>
            <style>
                body, html {
                    margin: 0;
                    padding: 0;
                    height: 100%;
                    overflow: hidden;
                    background: #f5f5f5;
                }
                iframe {
                    width: 100%;
                    height: 100vh;
                    border: none;
                    display: block;
                }
                .loading {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    font-family: Arial, sans-serif;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="loading">Loading secure content...</div>
            <iframe 
                src="' . htmlspecialchars($url) . '"
                sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-presentation"
                referrerpolicy="strict-origin-when-cross-origin"
                loading="lazy"
                onload="document.querySelector(".loading").style.display = "none""
            ></iframe>
            ' . $protectionScript . '
        </body>
        </html>
        ';
    }

    /**
     * Render security error page.
     * 
     * Shows user-friendly error message for security violations.
     * 
     * @param string $message Error message
     * @return \Illuminate\Http\Response
     */
    protected function renderSecurityError(string $message): Response
    {
        $html = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Access Denied</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: #f8f9fa;
                    margin: 0;
                    padding: 50px;
                    text-align: center;
                }
                .error-container {
                    max-width: 500px;
                    margin: 0 auto;
                    background: white;
                    padding: 40px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .error-icon {
                    font-size: 48px;
                    color: #dc3545;
                    margin-bottom: 20px;
                }
                h1 {
                    color: #dc3545;
                    margin-bottom: 20px;
                }
                p {
                    color: #666;
                    line-height: 1.6;
                }
                .btn {
                    display: inline-block;
                    padding: 10px 20px;
                    background: #007bff;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">🔒</div>
                <h1>Access Denied</h1>
                <p>' . htmlspecialchars($message) . '</p>
                <a href="/dashboard" class="btn">Return to Dashboard</a>
            </div>
        </body>
        </html>
        ';
        
        return response($html, 403)
            ->header('Content-Type', 'text/html')
            ->header('X-Frame-Options', 'DENY');
    }
}