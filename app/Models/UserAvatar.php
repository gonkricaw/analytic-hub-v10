<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasUuid;
use Carbon\Carbon;

class UserAvatar extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $table = 'idbi_user_avatars';

    protected $fillable = [
        'user_id',
        'filename',
        'stored_filename',
        'file_path',
        'file_url',
        'mime_type',
        'file_extension',
        'file_size',
        'file_hash',
        'width',
        'height',
        'aspect_ratio',
        'color_depth',
        'color_space',
        'exif_data',
        'dominant_color',
        'color_palette',
        'variants',
        'thumbnail_path',
        'small_path',
        'medium_path',
        'large_path',
        'variant_urls',
        'is_active',
        'is_default',
        'is_approved',
        'is_public',
        'status',
        'upload_source',
        'source_url',
        'social_provider',
        'social_id',
        'upload_metadata',
        'is_processed',
        'processed_at',
        'processing_log',
        'processing_error',
        'processing_attempts',
        'quality_score',
        'is_appropriate',
        'moderation_results',
        'requires_review',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'view_count',
        'first_used_at',
        'last_used_at',
        'download_count',
        'usage_statistics',
        'storage_driver',
        'storage_disk',
        'storage_bucket',
        'storage_region',
        'storage_metadata',
        'cdn_url',
        'is_cached',
        'cache_expires_at',
        'cache_key',
        'cdn_metadata',
        'is_encrypted',
        'encryption_method',
        'requires_authentication',
        'access_permissions',
        'access_token',
        'token_expires_at',
        'is_backed_up',
        'backed_up_at',
        'backup_location',
        'version',
        'previous_version_id',
        'version_history',
        'is_optimized',
        'original_size',
        'optimized_size',
        'compression_ratio',
        'optimization_settings',
        'analytics_data',
        'engagement_score',
        'performance_metrics',
        'last_analyzed_at',
        'has_consent',
        'consent_given_at',
        'compliance_data',
        'gdpr_compliant',
        'legal_notes',
        'expires_at',
        'auto_delete',
        'delete_after',
        'is_archived',
        'archived_at',
        'tags',
        'category',
        'description',
        'metadata',
        'uploaded_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'first_used_at' => 'datetime',
        'last_used_at' => 'datetime',
        'cache_expires_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'backed_up_at' => 'datetime',
        'last_analyzed_at' => 'datetime',
        'consent_given_at' => 'datetime',
        'expires_at' => 'datetime',
        'delete_after' => 'datetime',
        'archived_at' => 'datetime',
        'exif_data' => 'array',
        'color_palette' => 'array',
        'variants' => 'array',
        'variant_urls' => 'array',
        'upload_metadata' => 'array',
        'processing_log' => 'array',
        'moderation_results' => 'array',
        'usage_statistics' => 'array',
        'storage_metadata' => 'array',
        'cdn_metadata' => 'array',
        'access_permissions' => 'array',
        'version_history' => 'array',
        'optimization_settings' => 'array',
        'analytics_data' => 'array',
        'performance_metrics' => 'array',
        'compliance_data' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'aspect_ratio' => 'decimal:3',
        'compression_ratio' => 'decimal:3',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_approved' => 'boolean',
        'is_public' => 'boolean',
        'is_processed' => 'boolean',
        'is_appropriate' => 'boolean',
        'requires_review' => 'boolean',
        'is_cached' => 'boolean',
        'is_encrypted' => 'boolean',
        'requires_authentication' => 'boolean',
        'is_backed_up' => 'boolean',
        'is_optimized' => 'boolean',
        'has_consent' => 'boolean',
        'gdpr_compliant' => 'boolean',
        'auto_delete' => 'boolean',
        'is_archived' => 'boolean',
    ];

    // Constants
    const STATUSES = [
        'pending',
        'approved',
        'rejected',
        'processing'
    ];

    const UPLOAD_SOURCES = [
        'local',
        'url',
        'social',
        'gravatar',
        'generated'
    ];

    const STORAGE_DRIVERS = [
        'local',
        's3',
        'gcs',
        'azure',
        'ftp',
        'sftp'
    ];

    const SOCIAL_PROVIDERS = [
        'facebook',
        'google',
        'twitter',
        'linkedin',
        'github',
        'instagram'
    ];

    const QUALITY_THRESHOLDS = [
        'excellent' => 90,
        'good' => 70,
        'fair' => 50,
        'poor' => 30
    ];

    // Status check methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isDefault(): bool
    {
        return $this->is_default;
    }

    public function isApproved(): bool
    {
        return $this->is_approved && $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isProcessed(): bool
    {
        return $this->is_processed;
    }

    public function isPublic(): bool
    {
        return $this->is_public;
    }

    public function isAppropriate(): bool
    {
        return $this->is_appropriate;
    }

    public function requiresReview(): bool
    {
        return $this->requires_review;
    }

    public function isCached(): bool
    {
        return $this->is_cached && (!$this->cache_expires_at || $this->cache_expires_at->isFuture());
    }

    public function isEncrypted(): bool
    {
        return $this->is_encrypted;
    }

    public function isBackedUp(): bool
    {
        return $this->is_backed_up;
    }

    public function isOptimized(): bool
    {
        return $this->is_optimized;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isArchived(): bool
    {
        return $this->is_archived;
    }

    public function hasConsent(): bool
    {
        return $this->has_consent;
    }

    public function isGdprCompliant(): bool
    {
        return $this->gdpr_compliant;
    }

    public function requiresAuthentication(): bool
    {
        return $this->requires_authentication;
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(static::class, 'previous_version_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Utility methods
    public function getQualityLevel(): string
    {
        if (!$this->quality_score) return 'unknown';
        
        if ($this->quality_score >= self::QUALITY_THRESHOLDS['excellent']) return 'excellent';
        if ($this->quality_score >= self::QUALITY_THRESHOLDS['good']) return 'good';
        if ($this->quality_score >= self::QUALITY_THRESHOLDS['fair']) return 'fair';
        return 'poor';
    }

    public function getHumanReadableSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDimensions(): string
    {
        if (!$this->width || !$this->height) {
            return 'Unknown';
        }
        
        return "{$this->width} × {$this->height}";
    }

    public function getCompressionSavings(): ?string
    {
        if (!$this->original_size || !$this->optimized_size) {
            return null;
        }
        
        $savings = $this->original_size - $this->optimized_size;
        $percentage = ($savings / $this->original_size) * 100;
        
        return round($percentage, 1) . '%';
    }

    public function getAgeInDays(): int
    {
        return $this->created_at->diffInDays(now());
    }

    public function getHumanReadableAge(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }
        
        return max(0, now()->diffInDays($this->expires_at, false));
    }

    public function getUsageFrequency(): float
    {
        $ageInDays = max(1, $this->getAgeInDays());
        return round($this->view_count / $ageInDays, 2);
    }

    // Action methods
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function setAsDefault(): bool
    {
        // Remove default status from other avatars for this user
        static::where('user_id', $this->user_id)
              ->where('id', '!=', $this->id)
              ->update(['is_default' => false]);
        
        return $this->update(['is_default' => true, 'is_active' => true]);
    }

    public function approve(?string $userId = null): bool
    {
        return $this->update([
            'is_approved' => true,
            'status' => 'approved',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'requires_review' => false
        ]);
    }

    public function reject(string $reason = '', ?string $userId = null): bool
    {
        return $this->update([
            'is_approved' => false,
            'status' => 'rejected',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'review_notes' => $reason,
            'requires_review' => false,
            'is_active' => false
        ]);
    }

    public function markAsProcessing(): bool
    {
        return $this->update([
            'status' => 'processing',
            'is_processed' => false
        ]);
    }

    public function markAsProcessed(array $processingData = []): bool
    {
        return $this->update([
            'is_processed' => true,
            'processed_at' => now(),
            'status' => 'approved',
            'processing_log' => array_merge($this->processing_log ?? [], $processingData)
        ]);
    }

    public function recordView(): bool
    {
        return $this->increment('view_count', 1, [
            'last_used_at' => now(),
            'first_used_at' => $this->first_used_at ?? now()
        ]);
    }

    public function recordDownload(): bool
    {
        return $this->increment('download_count');
    }

    public function backup(string $location): bool
    {
        return $this->update([
            'is_backed_up' => true,
            'backed_up_at' => now(),
            'backup_location' => $location
        ]);
    }

    public function optimize(array $settings = []): bool
    {
        return $this->update([
            'is_optimized' => true,
            'optimization_settings' => $settings,
            'optimized_size' => $this->file_size // This would be updated with actual optimized size
        ]);
    }

    public function archive(?string $userId = null): bool
    {
        return $this->update([
            'is_archived' => true,
            'archived_at' => now(),
            'is_active' => false,
            'updated_by' => $userId
        ]);
    }

    public function getUrl(string $variant = 'original'): ?string
    {
        if ($variant === 'original') {
            return $this->file_url ?? Storage::disk($this->storage_disk)->url($this->file_path);
        }
        
        $variantUrls = $this->variant_urls ?? [];
        return $variantUrls[$variant] ?? null;
    }

    public function deleteFile(): bool
    {
        $deleted = true;
        
        // Delete main file
        if (Storage::disk($this->storage_disk)->exists($this->file_path)) {
            $deleted = Storage::disk($this->storage_disk)->delete($this->file_path);
        }
        
        // Delete variants
        $variants = $this->variants ?? [];
        foreach ($variants as $variant) {
            if (isset($variant['path']) && Storage::disk($this->storage_disk)->exists($variant['path'])) {
                Storage::disk($this->storage_disk)->delete($variant['path']);
            }
        }
        
        return $deleted;
    }

    // Static methods
    public static function getDefaultAvatar(string $userId): ?static
    {
        return static::where('user_id', $userId)
                    ->where('is_default', true)
                    ->where('is_active', true)
                    ->first();
    }

    public static function getUserAvatars(string $userId, bool $activeOnly = true): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('user_id', $userId);
        
        if ($activeOnly) {
            $query->where('is_active', true);
        }
        
        return $query->orderBy('is_default', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    public static function getPendingReviews(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('requires_review', true)
                    ->where('status', 'pending')
                    ->orderBy('created_at')
                    ->get();
    }

    public static function getExpiring(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return static::whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now(), now()->addDays($days)])
                    ->where('is_active', true)
                    ->orderBy('expires_at')
                    ->get();
    }

    public static function getStatistics(): array
    {
        return Cache::remember('user_avatars_stats', 600, function () {
            return [
                'total' => static::count(),
                'active' => static::where('is_active', true)->count(),
                'approved' => static::where('is_approved', true)->count(),
                'pending' => static::where('status', 'pending')->count(),
                'processing' => static::where('status', 'processing')->count(),
                'rejected' => static::where('status', 'rejected')->count(),
                'default' => static::where('is_default', true)->count(),
                'public' => static::where('is_public', true)->count(),
                'processed' => static::where('is_processed', true)->count(),
                'cached' => static::where('is_cached', true)->count(),
                'backed_up' => static::where('is_backed_up', true)->count(),
                'optimized' => static::where('is_optimized', true)->count(),
                'archived' => static::where('is_archived', true)->count(),
                'total_size' => static::sum('file_size'),
                'avg_quality' => static::avg('quality_score'),
                'total_views' => static::sum('view_count'),
                'total_downloads' => static::sum('download_count')
            ];
        });
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true)
                    ->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    public function scopeRequiringReview($query)
    {
        return $query->where('requires_review', true);
    }

    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('upload_source', $source);
    }

    public function scopeByMimeType($query, string $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    public function scopeHighQuality($query, int $threshold = 70)
    {
        return $query->where('quality_score', '>=', $threshold);
    }

    public function scopeLowQuality($query, int $threshold = 50)
    {
        return $query->where('quality_score', '<', $threshold);
    }

    public function scopeLargeFiles($query, int $sizeInBytes = 1048576) // 1MB
    {
        return $query->where('file_size', '>', $sizeInBytes);
    }

    public function scopeCached($query)
    {
        return $query->where('is_cached', true)
                    ->where(function ($q) {
                        $q->whereNull('cache_expires_at')
                          ->orWhere('cache_expires_at', '>', now());
                    });
    }

    public function scopeBackedUp($query)
    {
        return $query->where('is_backed_up', true);
    }

    public function scopeOptimized($query)
    {
        return $query->where('is_optimized', true);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '<', now());
    }

    public function scopeExpiring($query, int $days = 7)
    {
        return $query->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopePopular($query, int $minViews = 10)
    {
        return $query->where('view_count', '>=', $minViews)
                    ->orderBy('view_count', 'desc');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('filename', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('category', 'like', "%{$term}%")
              ->orWhereJsonContains('tags', $term);
        });
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();
        
        static::saved(function () {
            Cache::forget('user_avatars_stats');
        });
        
        static::deleted(function ($avatar) {
            // Clean up files when avatar is deleted
            $avatar->deleteFile();
            Cache::forget('user_avatars_stats');
        });
    }
}