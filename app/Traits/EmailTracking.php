<?php

namespace App\Traits;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * EmailTracking Trait
 * 
 * Provides email tracking functionality for mailables and email templates.
 * Generates tracking URLs for opens, clicks, and unsubscribe actions.
 * 
 * Features:
 * - Open tracking pixel generation
 * - Click tracking URL wrapping
 * - Unsubscribe link generation
 * - Template variable replacement with tracking
 * - Secure tracking URL generation
 * 
 * @package App\Traits
 * @author Analytics Hub Team
 * @version 1.0.0
 */
trait EmailTracking
{
    /**
     * Generate tracking pixel URL for email opens
     * 
     * @param string $messageId
     * @return string
     */
    public function generateOpenTrackingUrl(string $messageId): string
    {
        return route('email.track.open', ['messageId' => $messageId]);
    }

    /**
     * Generate click tracking URL
     * 
     * @param string $messageId
     * @param string $originalUrl
     * @return string
     */
    public function generateClickTrackingUrl(string $messageId, string $originalUrl): string
    {
        return route('email.track.click', [
            'messageId' => $messageId,
            'url' => urlencode($originalUrl)
        ]);
    }

    /**
     * Generate unsubscribe URL
     * 
     * @param string $messageId
     * @param string|null $email
     * @return string
     */
    public function generateUnsubscribeUrl(string $messageId, ?string $email = null): string
    {
        $params = ['messageId' => $messageId];
        
        if ($email) {
            $params['email'] = $email;
        }
        
        return route('email.unsubscribe', $params);
    }

    /**
     * Add tracking to email content
     * 
     * @param string $content
     * @param string $messageId
     * @param bool $trackOpens
     * @param bool $trackClicks
     * @return string
     */
    public function addTrackingToContent(string $content, string $messageId, bool $trackOpens = true, bool $trackClicks = true): string
    {
        // Add open tracking pixel
        if ($trackOpens) {
            $content = $this->addOpenTrackingPixel($content, $messageId);
        }
        
        // Add click tracking to links
        if ($trackClicks) {
            $content = $this->addClickTracking($content, $messageId);
        }
        
        return $content;
    }

    /**
     * Add open tracking pixel to email content
     * 
     * @param string $content
     * @param string $messageId
     * @return string
     */
    protected function addOpenTrackingPixel(string $content, string $messageId): string
    {
        $trackingUrl = $this->generateOpenTrackingUrl($messageId);
        $trackingPixel = '<img src="' . $trackingUrl . '" width="1" height="1" style="display:none;" alt="" />';
        
        // Try to insert before closing body tag, otherwise append
        if (stripos($content, '</body>') !== false) {
            $content = str_ireplace('</body>', $trackingPixel . '</body>', $content);
        } else {
            $content .= $trackingPixel;
        }
        
        return $content;
    }

    /**
     * Add click tracking to all links in content
     * 
     * @param string $content
     * @param string $messageId
     * @return string
     */
    protected function addClickTracking(string $content, string $messageId): string
    {
        // Pattern to match href attributes in anchor tags
        $pattern = '/href=["\']([^"\'>]+)["\']/';
        
        return preg_replace_callback($pattern, function ($matches) use ($messageId) {
            $originalUrl = $matches[1];
            
            // Skip tracking for certain URLs
            if ($this->shouldSkipTracking($originalUrl)) {
                return $matches[0];
            }
            
            $trackingUrl = $this->generateClickTrackingUrl($messageId, $originalUrl);
            return 'href="' . $trackingUrl . '"';
        }, $content);
    }

    /**
     * Check if URL should be skipped from tracking
     * 
     * @param string $url
     * @return bool
     */
    protected function shouldSkipTracking(string $url): bool
    {
        // Skip tracking for:
        // - Mailto links
        // - Tel links
        // - Anchor links
        // - Unsubscribe links (already tracked)
        // - Tracking URLs (prevent double tracking)
        
        $skipPatterns = [
            '/^mailto:/',
            '/^tel:/',
            '/^#/',
            '/\/email\/track\//i',
            '/\/email\/unsubscribe\//i',
            '/\/email\/webhook\//i'
        ];
        
        foreach ($skipPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Replace template variables with tracking-enabled content
     * 
     * @param string $content
     * @param array $variables
     * @param string $messageId
     * @return string
     */
    public function replaceVariablesWithTracking(string $content, array $variables, string $messageId): string
    {
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            
            // Special handling for URLs
            if (is_string($value) && $this->isUrl($value)) {
                $value = $this->generateClickTrackingUrl($messageId, $value);
            }
            
            // Special handling for unsubscribe URLs
            if ($key === 'unsubscribe_url' || $key === 'unsubscribe_link') {
                $email = $variables['user_email'] ?? $variables['email'] ?? null;
                $value = $this->generateUnsubscribeUrl($messageId, $email);
            }
            
            $content = str_replace($placeholder, $value, $content);
        }
        
        return $content;
    }

    /**
     * Check if a string is a valid URL
     * 
     * @param string $string
     * @return bool
     */
    protected function isUrl(string $string): bool
    {
        return filter_var($string, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Generate tracking data for email
     * 
     * @param string $messageId
     * @param array $additionalData
     * @return array
     */
    public function generateTrackingData(string $messageId, array $additionalData = []): array
    {
        return array_merge([
            'message_id' => $messageId,
            'tracking_id' => Str::uuid()->toString(),
            'created_at' => now()->toISOString(),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
        ], $additionalData);
    }

    /**
     * Create email tracking variables for templates
     * 
     * @param string $messageId
     * @param string|null $userEmail
     * @return array
     */
    public function createTrackingVariables(string $messageId, ?string $userEmail = null): array
    {
        return [
            'tracking_pixel' => $this->generateOpenTrackingUrl($messageId),
            'unsubscribe_url' => $this->generateUnsubscribeUrl($messageId, $userEmail),
            'message_id' => $messageId,
        ];
    }

    /**
     * Wrap email content with tracking
     * 
     * @param string $content
     * @param string $messageId
     * @param array $options
     * @return string
     */
    public function wrapWithTracking(string $content, string $messageId, array $options = []): string
    {
        $trackOpens = $options['track_opens'] ?? true;
        $trackClicks = $options['track_clicks'] ?? true;
        $addUnsubscribe = $options['add_unsubscribe'] ?? true;
        $userEmail = $options['user_email'] ?? null;
        
        // Add tracking to content
        $content = $this->addTrackingToContent($content, $messageId, $trackOpens, $trackClicks);
        
        // Add unsubscribe link if requested and not already present
        if ($addUnsubscribe && !str_contains($content, 'unsubscribe')) {
            $content = $this->addUnsubscribeLink($content, $messageId, $userEmail);
        }
        
        return $content;
    }

    /**
     * Add unsubscribe link to email content
     * 
     * @param string $content
     * @param string $messageId
     * @param string|null $userEmail
     * @return string
     */
    protected function addUnsubscribeLink(string $content, string $messageId, ?string $userEmail = null): string
    {
        $unsubscribeUrl = $this->generateUnsubscribeUrl($messageId, $userEmail);
        $unsubscribeLink = '<p style="font-size: 12px; color: #666; text-align: center; margin-top: 20px;">' .
                          'If you no longer wish to receive these emails, you can ' .
                          '<a href="' . $unsubscribeUrl . '" style="color: #666;">unsubscribe here</a>.' .
                          '</p>';
        
        // Try to insert before closing body tag, otherwise append
        if (stripos($content, '</body>') !== false) {
            $content = str_ireplace('</body>', $unsubscribeLink . '</body>', $content);
        } else {
            $content .= $unsubscribeLink;
        }
        
        return $content;
    }

    /**
     * Generate secure tracking token
     * 
     * @param string $messageId
     * @param string $action
     * @return string
     */
    public function generateTrackingToken(string $messageId, string $action): string
    {
        $data = [
            'message_id' => $messageId,
            'action' => $action,
            'timestamp' => time(),
        ];
        
        return base64_encode(json_encode($data));
    }

    /**
     * Verify tracking token
     * 
     * @param string $token
     * @param int $maxAge Maximum age in seconds (default: 30 days)
     * @return array|null
     */
    public function verifyTrackingToken(string $token, int $maxAge = 2592000): ?array
    {
        try {
            $data = json_decode(base64_decode($token), true);
            
            if (!$data || !isset($data['timestamp'])) {
                return null;
            }
            
            // Check if token is not expired
            if (time() - $data['timestamp'] > $maxAge) {
                return null;
            }
            
            return $data;
            
        } catch (\Exception $e) {
            return null;
        }
    }
}