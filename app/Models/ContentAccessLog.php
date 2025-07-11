<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;

/**
 * ContentAccessLog Model
 * 
 * Handles access tracking and security auditing for content management.
 * Logs all content access attempts for security monitoring and analytics.
 * 
 * Features:
 * - Content access tracking
 * - Security auditing and monitoring
 * - User behavior analytics
 * - Geographic and device tracking
 * - Suspicious activity detection
 * - Performance monitoring
 * 
 * @property int $id
 * @property int $content_id
 * @property int|null $user_id
 * @property string|null $user_email
 * @property string|null $user_role
 * @property string $access_type
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string|null $session_id
 * @property string|null $access_token
 * @property Carbon|null $token_expires_at
 * @property string|null $referer
 * @property string $request_method
 * @property array|null $request_headers
 * @property array|null $request_params
 * @property string|null $country_code
 * @property string|null $city
 * @property string|null $device_type
 * @property string|null $browser
 * @property string|null $platform
 * @property string|null $content_uuid
 * @property string|null $content_type
 * @property string|null $content_title
 * @property string $access_result
 * @property string|null $denial_reason
 * @property string|null $error_message
 * @property int|null $response_time_ms
 * @property int|null $bytes_transferred
 * @property int|null $session_duration
 * @property bool $is_suspicious
 * @property bool $is_bot
 * @property bool $is_vpn
 * @property bool $is_tor
 * @property array|null $metadata
 * @property string|null $notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @author Analytics Hub Team
 * @version 1.0
 * @since 2024-01-01
 */
class ContentAccessLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'content_id',
        'user_id',
        'user_email',
        'user_role',
        'access_type',
        'ip_address',
        'user_agent',
        'session_id',
        'access_token',
        'token_expires_at',
        'referer',
        'request_method',
        'request_headers',
        'request_params',
        'country_code',
        'city',
        'device_type',
        'browser',
        'platform',
        'content_uuid',
        'content_type',
        'content_title',
        'access_result',
        'denial_reason',
        'error_message',
        'response_time_ms',
        'bytes_transferred',
        'session_duration',
        'is_suspicious',
        'is_bot',
        'is_vpn',
        'is_tor',
        'metadata',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'token_expires_at' => 'datetime',
        'request_headers' => 'array',
        'request_params' => 'array',
        'response_time_ms' => 'integer',
        'bytes_transferred' => 'integer',
        'session_duration' => 'integer',
        'is_suspicious' => 'boolean',
        'is_bot' => 'boolean',
        'is_vpn' => 'boolean',
        'is_tor' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Access type constants
     */
    const ACCESS_TYPE_VIEW = 'view';
    const ACCESS_TYPE_EMBED = 'embed';
    const ACCESS_TYPE_SECURE_VIEW = 'secure_view';
    const ACCESS_TYPE_DOWNLOAD = 'download';
    const ACCESS_TYPE_SHARE = 'share';
    const ACCESS_TYPE_TOKEN_GENERATE = 'token_generate';
    const ACCESS_TYPE_TOKEN_USE = 'token_use';

    /**
     * Access result constants
     */
    const RESULT_SUCCESS = 'success';
    const RESULT_DENIED = 'denied';
    const RESULT_EXPIRED = 'expired';
    const RESULT_INVALID_TOKEN = 'invalid_token';
    const RESULT_RATE_LIMITED = 'rate_limited';
    const RESULT_BLOCKED = 'blocked';
    const RESULT_ERROR = 'error';

    /**
     * Device type constants
     */
    const DEVICE_DESKTOP = 'desktop';
    const DEVICE_MOBILE = 'mobile';
    const DEVICE_TABLET = 'tablet';
    const DEVICE_BOT = 'bot';

    /**
     * Get the content that was accessed.
     *
     * @return BelongsTo
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    /**
     * Get the user who accessed the content.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for successful access logs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('access_result', self::RESULT_SUCCESS);
    }

    /**
     * Scope for failed access logs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('access_result', '!=', self::RESULT_SUCCESS);
    }

    /**
     * Scope for suspicious access logs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * Scope for bot access logs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBots($query)
    {
        return $query->where('is_bot', true);
    }

    /**
     * Scope for access logs by IP address.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $ipAddress
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByIp($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope for access logs by access type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $accessType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAccessType($query, string $accessType)
    {
        return $query->where('access_type', $accessType);
    }

    /**
     * Scope for access logs by content.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $contentId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByContent($query, int $contentId)
    {
        return $query->where('content_id', $contentId);
    }

    /**
     * Scope for access logs by user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for access logs within date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for recent access logs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hours
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Create a new access log entry.
     *
     * @param int $contentId
     * @param string $accessType
     * @param string $accessResult
     * @param array $options
     * @return static
     */
    public static function logAccess(
        int $contentId,
        string $accessType,
        string $accessResult,
        array $options = []
    ): static {
        $user = auth()->user();
        $request = request();
        
        return static::create(array_merge([
            'content_id' => $contentId,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_role' => $user?->role,
            'access_type' => $accessType,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'referer' => $request->header('referer'),
            'request_method' => $request->method(),
            'access_result' => $accessResult,
            'device_type' => static::detectDeviceType($request->userAgent()),
            'browser' => static::detectBrowser($request->userAgent()),
            'platform' => static::detectPlatform($request->userAgent()),
            'is_bot' => static::detectBot($request->userAgent()),
        ], $options));
    }

    /**
     * Detect device type from user agent.
     *
     * @param string|null $userAgent
     * @return string
     */
    protected static function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return self::DEVICE_DESKTOP;
        }

        $userAgent = strtolower($userAgent);

        if (static::detectBot($userAgent)) {
            return self::DEVICE_BOT;
        }

        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $userAgent)) {
            return self::DEVICE_TABLET;
        }

        if (preg_match('/(mobile|iphone|ipod|blackberry|android|palm|windows\sce)/i', $userAgent)) {
            return self::DEVICE_MOBILE;
        }

        return self::DEVICE_DESKTOP;
    }

    /**
     * Detect browser from user agent.
     *
     * @param string|null $userAgent
     * @return string|null
     */
    protected static function detectBrowser(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }

        $browsers = [
            'Chrome' => '/chrome\/(\d+)/i',
            'Firefox' => '/firefox\/(\d+)/i',
            'Safari' => '/safari\/(\d+)/i',
            'Edge' => '/edge\/(\d+)/i',
            'Opera' => '/opera\/(\d+)/i',
            'Internet Explorer' => '/msie\s(\d+)/i',
        ];

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $userAgent, $matches)) {
                return $browser . ' ' . ($matches[1] ?? '');
            }
        }

        return 'Unknown';
    }

    /**
     * Detect platform from user agent.
     *
     * @param string|null $userAgent
     * @return string|null
     */
    protected static function detectPlatform(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }

        $platforms = [
            'Windows' => '/windows/i',
            'macOS' => '/macintosh|mac os x/i',
            'Linux' => '/linux/i',
            'Android' => '/android/i',
            'iOS' => '/iphone|ipad|ipod/i',
        ];

        foreach ($platforms as $platform => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $platform;
            }
        }

        return 'Unknown';
    }

    /**
     * Detect if user agent is a bot.
     *
     * @param string|null $userAgent
     * @return bool
     */
    protected static function detectBot(?string $userAgent): bool
    {
        if (!$userAgent) {
            return false;
        }

        $botPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/googlebot/i',
            '/bingbot/i',
            '/slurp/i',
            '/duckduckbot/i',
            '/baiduspider/i',
            '/yandexbot/i',
            '/facebookexternalhit/i',
            '/twitterbot/i',
            '/linkedinbot/i',
            '/whatsapp/i',
            '/telegram/i',
        ];

        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mark this access log as suspicious.
     *
     * @param string|null $reason
     * @return void
     */
    public function markAsSuspicious(?string $reason = null): void
    {
        $this->update([
            'is_suspicious' => true,
            'notes' => $reason ? "Suspicious: {$reason}" : 'Marked as suspicious',
        ]);
    }

    /**
     * Get formatted access duration.
     *
     * @return string
     */
    public function getFormattedDuration(): string
    {
        if (!$this->session_duration) {
            return 'N/A';
        }

        $minutes = floor($this->session_duration / 60);
        $seconds = $this->session_duration % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }

        return "{$seconds}s";
    }

    /**
     * Get formatted response time.
     *
     * @return string
     */
    public function getFormattedResponseTime(): string
    {
        if (!$this->response_time_ms) {
            return 'N/A';
        }

        if ($this->response_time_ms >= 1000) {
            return round($this->response_time_ms / 1000, 2) . 's';
        }

        return $this->response_time_ms . 'ms';
    }

    /**
     * Get formatted bytes transferred.
     *
     * @return string
     */
    public function getFormattedBytesTransferred(): string
    {
        if (!$this->bytes_transferred) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->bytes_transferred;
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get available access types.
     *
     * @return array
     */
    public static function getAccessTypes(): array
    {
        return [
            self::ACCESS_TYPE_VIEW => 'View',
            self::ACCESS_TYPE_EMBED => 'Embed',
            self::ACCESS_TYPE_SECURE_VIEW => 'Secure View',
            self::ACCESS_TYPE_DOWNLOAD => 'Download',
            self::ACCESS_TYPE_SHARE => 'Share',
            self::ACCESS_TYPE_TOKEN_GENERATE => 'Token Generate',
            self::ACCESS_TYPE_TOKEN_USE => 'Token Use',
        ];
    }

    /**
     * Get available access results.
     *
     * @return array
     */
    public static function getAccessResults(): array
    {
        return [
            self::RESULT_SUCCESS => 'Success',
            self::RESULT_DENIED => 'Denied',
            self::RESULT_EXPIRED => 'Expired',
            self::RESULT_INVALID_TOKEN => 'Invalid Token',
            self::RESULT_RATE_LIMITED => 'Rate Limited',
            self::RESULT_BLOCKED => 'Blocked',
            self::RESULT_ERROR => 'Error',
        ];
    }

    /**
     * Get available device types.
     *
     * @return array
     */
    public static function getDeviceTypes(): array
    {
        return [
            self::DEVICE_DESKTOP => 'Desktop',
            self::DEVICE_MOBILE => 'Mobile',
            self::DEVICE_TABLET => 'Tablet',
            self::DEVICE_BOT => 'Bot',
        ];
    }
}