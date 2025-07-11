<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * Class ContentEncryptionService
 * 
 * Handles AES-256 encryption/decryption for embedded content URLs.
 * Implements UUID-based URL masking and secure content serving.
 * Provides browser inspection protection and access logging.
 * 
 * @package App\Services
 */
class ContentEncryptionService
{
    /**
     * Encryption key prefix for content URLs
     */
    private const URL_PREFIX = 'content_url:';
    
    /**
     * UUID namespace for URL masking
     */
    private const UUID_NAMESPACE = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    /**
     * Encrypt a URL using AES-256 encryption.
     * 
     * Encrypts the provided URL with Laravel's built-in encryption
     * which uses AES-256-CBC by default. Adds prefix for identification.
     * 
     * @param string $url Original URL to encrypt
     * @return string Encrypted URL string
     * @throws \Exception If encryption fails
     */
    public function encryptUrl(string $url): string
    {
        try {
            // Validate URL format
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('Invalid URL format provided');
            }

            // Add prefix and encrypt
            $prefixedUrl = self::URL_PREFIX . $url;
            $encrypted = Crypt::encrypt($prefixedUrl);

            Log::info('URL encrypted successfully', [
                'url_hash' => hash('sha256', $url),
                'encrypted_length' => strlen($encrypted)
            ]);

            return $encrypted;

        } catch (\Exception $e) {
            Log::error('URL encryption failed', [
                'error' => $e->getMessage(),
                'url_hash' => hash('sha256', $url)
            ]);
            
            throw new \Exception('Failed to encrypt URL: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt an encrypted URL.
     * 
     * Decrypts the URL and validates the prefix to ensure
     * it's a legitimate content URL encryption.
     * 
     * @param string $encryptedUrl Encrypted URL string
     * @return string Original decrypted URL
     * @throws \Exception If decryption fails or URL is invalid
     */
    public function decryptUrl(string $encryptedUrl): string
    {
        try {
            // Decrypt the URL
            $decrypted = Crypt::decrypt($encryptedUrl);

            // Validate prefix
            if (!str_starts_with($decrypted, self::URL_PREFIX)) {
                throw new \InvalidArgumentException('Invalid encrypted URL format');
            }

            // Remove prefix and return original URL
            $originalUrl = substr($decrypted, strlen(self::URL_PREFIX));

            // Validate decrypted URL
            if (!filter_var($originalUrl, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('Decrypted content is not a valid URL');
            }

            Log::info('URL decrypted successfully', [
                'url_hash' => hash('sha256', $originalUrl)
            ]);

            return $originalUrl;

        } catch (DecryptException $e) {
            Log::error('URL decryption failed - invalid encryption', [
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('Failed to decrypt URL: Invalid encryption data');
            
        } catch (\Exception $e) {
            Log::error('URL decryption failed', [
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('Failed to decrypt URL: ' . $e->getMessage());
        }
    }

    /**
     * Generate a UUID-based masked URL for the original URL.
     * 
     * Creates a deterministic UUID based on the URL content
     * for consistent masking while maintaining uniqueness.
     * 
     * @param string $url Original URL
     * @return string UUID-based masked identifier
     */
    public function generateMaskedUrl(string $url): string
    {
        try {
            // Create a deterministic UUID based on URL content
            $urlHash = hash('sha256', $url);
            
            // Generate UUID v5 (namespace + name based)
            $uuid = $this->generateUuidV5(self::UUID_NAMESPACE, $urlHash);

            Log::info('Masked URL generated', [
                'url_hash' => hash('sha256', $url),
                'masked_id' => $uuid
            ]);

            return $uuid;

        } catch (\Exception $e) {
            Log::error('Masked URL generation failed', [
                'error' => $e->getMessage(),
                'url_hash' => hash('sha256', $url)
            ]);
            
            // Fallback to random UUID if deterministic generation fails
            return Str::uuid()->toString();
        }
    }

    /**
     * Generate a one-time access token for secure content viewing.
     * 
     * Creates a temporary token that expires after a short period
     * to prevent unauthorized access to decrypted content.
     * 
     * @param string $contentId Content identifier
     * @param int $expiryMinutes Token expiry in minutes (default: 30)
     * @return array Token data with expiry information
     */
    public function generateOneTimeToken(string $contentId, int $expiryMinutes = 30): array
    {
        try {
            $token = Str::random(64);
            $expiresAt = now()->addMinutes($expiryMinutes);
            
            $tokenData = [
                'token' => $token,
                'content_id' => $contentId,
                'expires_at' => $expiresAt->toISOString(),
                'created_at' => now()->toISOString(),
                'user_id' => auth()->id()
            ];

            // Encrypt token data
            $encryptedToken = Crypt::encrypt(json_encode($tokenData));

            Log::info('One-time token generated', [
                'content_id' => $contentId,
                'expires_at' => $expiresAt->toISOString(),
                'user_id' => auth()->id()
            ]);

            return [
                'token' => $encryptedToken,
                'expires_at' => $expiresAt,
                'expires_in_seconds' => $expiryMinutes * 60
            ];

        } catch (\Exception $e) {
            Log::error('One-time token generation failed', [
                'content_id' => $contentId,
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('Failed to generate access token: ' . $e->getMessage());
        }
    }

    /**
     * Validate and decode a one-time access token.
     * 
     * Checks token validity, expiry, and user permissions
     * before allowing access to encrypted content.
     * 
     * @param string $encryptedToken Encrypted token string
     * @return array|null Token data if valid, null if invalid/expired
     */
    public function validateOneTimeToken(string $encryptedToken): ?array
    {
        try {
            // Decrypt token data
            $tokenJson = Crypt::decrypt($encryptedToken);
            $tokenData = json_decode($tokenJson, true);

            if (!$tokenData || !isset($tokenData['expires_at'], $tokenData['content_id'])) {
                Log::warning('Invalid token format', ['token_length' => strlen($encryptedToken)]);
                return null;
            }

            // Check expiry
            $expiresAt = \Carbon\Carbon::parse($tokenData['expires_at']);
            if ($expiresAt->isPast()) {
                Log::info('Token expired', [
                    'content_id' => $tokenData['content_id'],
                    'expired_at' => $expiresAt->toISOString()
                ]);
                return null;
            }

            // Validate user (if token was created for specific user)
            if (isset($tokenData['user_id']) && $tokenData['user_id'] !== auth()->id()) {
                Log::warning('Token user mismatch', [
                    'token_user_id' => $tokenData['user_id'],
                    'current_user_id' => auth()->id(),
                    'content_id' => $tokenData['content_id']
                ]);
                return null;
            }

            Log::info('Token validated successfully', [
                'content_id' => $tokenData['content_id'],
                'user_id' => auth()->id()
            ]);

            return $tokenData;

        } catch (DecryptException $e) {
            Log::warning('Token decryption failed', ['error' => $e->getMessage()]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('Token validation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate browser inspection protection JavaScript.
     * 
     * Creates JavaScript code to prevent common inspection methods
     * like right-click, F12, Ctrl+U, etc.
     * 
     * @return string JavaScript code for protection
     */
    public function generateInspectionProtection(): string
    {
        return '
        <script>
        (function() {
            "use strict";
            
            // Disable right-click context menu
            document.addEventListener("contextmenu", function(e) {
                e.preventDefault();
                return false;
            });
            
            // Disable common keyboard shortcuts
            document.addEventListener("keydown", function(e) {
                // F12 (Developer Tools)
                if (e.keyCode === 123) {
                    e.preventDefault();
                    return false;
                }
                
                // Ctrl+Shift+I (Developer Tools)
                if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
                    e.preventDefault();
                    return false;
                }
                
                // Ctrl+Shift+C (Element Inspector)
                if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
                    e.preventDefault();
                    return false;
                }
                
                // Ctrl+U (View Source)
                if (e.ctrlKey && e.keyCode === 85) {
                    e.preventDefault();
                    return false;
                }
                
                // Ctrl+S (Save Page)
                if (e.ctrlKey && e.keyCode === 83) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Disable text selection
            document.addEventListener("selectstart", function(e) {
                e.preventDefault();
                return false;
            });
            
            // Disable drag and drop
            document.addEventListener("dragstart", function(e) {
                e.preventDefault();
                return false;
            });
            
            // Clear console periodically
            setInterval(function() {
                if (typeof console !== "undefined" && console.clear) {
                    console.clear();
                }
            }, 1000);
            
            // Detect developer tools
            let devtools = {
                open: false,
                orientation: null
            };
            
            setInterval(function() {
                if (window.outerHeight - window.innerHeight > 200 || 
                    window.outerWidth - window.innerWidth > 200) {
                    if (!devtools.open) {
                        devtools.open = true;
                        // Redirect or show warning
                        window.location.href = "/";
                    }
                } else {
                    devtools.open = false;
                }
            }, 500);
            
        })();
        </script>
        ';
    }

    /**
     * Generate UUID v5 (namespace + name based).
     * 
     * Creates a deterministic UUID based on namespace and name
     * for consistent URL masking.
     * 
     * @param string $namespace UUID namespace
     * @param string $name Name to hash
     * @return string UUID v5 string
     */
    private function generateUuidV5(string $namespace, string $name): string
    {
        // Convert namespace to binary
        $nhex = str_replace(['-', '{', '}'], '', $namespace);
        $nstr = '';
        
        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }
        
        // Calculate hash
        $hash = sha1($nstr . $name);
        
        // Format as UUID v5
        return sprintf(
            '%08s-%04s-%04x-%04x-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
            substr($hash, 20, 12)
        );
    }

    /**
     * Log content access for audit purposes.
     * 
     * Records content access attempts with user information,
     * IP address, and timestamp for security monitoring.
     * 
     * @param string $contentId Content identifier
     * @param string $action Access action (view, download, etc.)
     * @param array $metadata Additional metadata
     * @return void
     */
    public function logContentAccess(string $contentId, string $action = 'view', array $metadata = []): void
    {
        try {
            $logData = [
                'content_id' => $contentId,
                'action' => $action,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
                'metadata' => $metadata
            ];

            Log::info('Content access logged', $logData);

            // Here you could also store in a dedicated content_access_logs table
            // for more detailed analytics and reporting

        } catch (\Exception $e) {
            Log::error('Failed to log content access', [
                'content_id' => $contentId,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate if URL is from allowed domains.
     * 
     * Checks if the embedded URL is from trusted platforms
     * like Power BI, Tableau, Google Data Studio, etc.
     * 
     * @param string $url URL to validate
     * @return bool True if URL is from allowed domain
     */
    public function isAllowedDomain(string $url): bool
    {
        $allowedDomains = [
            'app.powerbi.com',
            'public.tableau.com',
            'datastudio.google.com',
            'lookerstudio.google.com',
            'embed.looker.com',
            'qlikview.com',
            'qliksense.com',
            'domo.com'
        ];

        $parsedUrl = parse_url($url);
        
        if (!isset($parsedUrl['host'])) {
            return false;
        }

        $host = strtolower($parsedUrl['host']);
        
        foreach ($allowedDomains as $allowedDomain) {
            if ($host === $allowedDomain || str_ends_with($host, '.' . $allowedDomain)) {
                return true;
            }
        }

        return false;
    }
}