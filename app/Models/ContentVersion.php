<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * ContentVersion Model
 * 
 * Handles content version history and tracking for rollback functionality.
 * Stores snapshots of content at different points in time.
 * 
 * Features:
 * - Content version snapshots
 * - Change tracking and audit trail
 * - Version comparison support
 * - Rollback functionality
 * - User attribution for changes
 * 
 * @property int $id
 * @property int $content_id
 * @property int $version_number
 * @property string|null $description
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string|null $content
 * @property string $type
 * @property string $status
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property bool $is_featured
 * @property bool $allow_comments
 * @property bool $is_searchable
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property string|null $featured_image
 * @property array|null $visibility_settings
 * @property array|null $access_permissions
 * @property string|null $template
 * @property array|null $layout_settings
 * @property array|null $custom_fields
 * @property int $created_by
 * @property string $change_type
 * @property array|null $changes_summary
 * @property int $view_count_snapshot
 * @property int $like_count_snapshot
 * @property int $share_count_snapshot
 * @property int $comment_count_snapshot
 * @property float $rating_snapshot
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @author Analytics Hub Team
 * @version 1.0
 * @since 2024-01-01
 */
class ContentVersion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'content_id',
        'version_number',
        'description',
        'title',
        'slug',
        'excerpt',
        'content',
        'type',
        'status',
        'published_at',
        'expires_at',
        'is_featured',
        'allow_comments',
        'is_searchable',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'featured_image',
        'visibility_settings',
        'access_permissions',
        'template',
        'layout_settings',
        'custom_fields',
        'created_by',
        'change_type',
        'changes_summary',
        'view_count_snapshot',
        'like_count_snapshot',
        'share_count_snapshot',
        'comment_count_snapshot',
        'rating_snapshot',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_featured' => 'boolean',
        'allow_comments' => 'boolean',
        'is_searchable' => 'boolean',
        'visibility_settings' => 'array',
        'access_permissions' => 'array',
        'layout_settings' => 'array',
        'custom_fields' => 'array',
        'changes_summary' => 'array',
        'view_count_snapshot' => 'integer',
        'like_count_snapshot' => 'integer',
        'share_count_snapshot' => 'integer',
        'comment_count_snapshot' => 'integer',
        'rating_snapshot' => 'decimal:2',
    ];

    /**
     * Change type constants
     */
    const CHANGE_TYPE_CREATE = 'create';
    const CHANGE_TYPE_UPDATE = 'update';
    const CHANGE_TYPE_RESTORE = 'restore';
    const CHANGE_TYPE_PUBLISH = 'publish';
    const CHANGE_TYPE_ARCHIVE = 'archive';
    const CHANGE_TYPE_FEATURE = 'feature';

    /**
     * Get the content that this version belongs to.
     *
     * @return BelongsTo
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    /**
     * Get the user who created this version.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get versions for a specific content.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $contentId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForContent($query, int $contentId)
    {
        return $query->where('content_id', $contentId);
    }

    /**
     * Scope to get versions by change type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $changeType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByChangeType($query, string $changeType)
    {
        return $query->where('change_type', $changeType);
    }

    /**
     * Scope to get versions created by a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCreator($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope to get latest versions first.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('version_number', 'desc');
    }

    /**
     * Scope to get oldest versions first.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOldestFirst($query)
    {
        return $query->orderBy('version_number', 'asc');
    }

    /**
     * Check if this is the latest version.
     *
     * @return bool
     */
    public function isLatest(): bool
    {
        $latestVersion = static::where('content_id', $this->content_id)
            ->max('version_number');
        
        return $this->version_number === $latestVersion;
    }

    /**
     * Get the previous version.
     *
     * @return ContentVersion|null
     */
    public function getPreviousVersion(): ?ContentVersion
    {
        return static::where('content_id', $this->content_id)
            ->where('version_number', '<', $this->version_number)
            ->orderBy('version_number', 'desc')
            ->first();
    }

    /**
     * Get the next version.
     *
     * @return ContentVersion|null
     */
    public function getNextVersion(): ?ContentVersion
    {
        return static::where('content_id', $this->content_id)
            ->where('version_number', '>', $this->version_number)
            ->orderBy('version_number', 'asc')
            ->first();
    }

    /**
     * Get version display name.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return "v{$this->version_number}" . 
               ($this->description ? " - {$this->description}" : '');
    }

    /**
     * Get formatted change summary.
     *
     * @return string
     */
    public function getFormattedChangesSummary(): string
    {
        if (empty($this->changes_summary)) {
            return 'No changes recorded';
        }

        $changes = [];
        foreach ($this->changes_summary as $field => $change) {
            if (is_array($change) && isset($change['from'], $change['to'])) {
                $changes[] = ucfirst(str_replace('_', ' ', $field)) . ' changed';
            } else {
                $changes[] = ucfirst(str_replace('_', ' ', $field)) . ' updated';
            }
        }

        return implode(', ', $changes);
    }

    /**
     * Restore this version to the main content.
     *
     * @param int $userId
     * @return bool
     */
    public function restoreToContent(int $userId): bool
    {
        $content = $this->content;
        if (!$content) {
            return false;
        }

        // Create a new version before restoring
        $content->createVersion(
            "Restored from version {$this->version_number}",
            $userId,
            self::CHANGE_TYPE_RESTORE
        );

        // Update content with this version's data
        $content->update([
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'type' => $this->type,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'expires_at' => $this->expires_at,
            'is_featured' => $this->is_featured,
            'allow_comments' => $this->allow_comments,
            'is_searchable' => $this->is_searchable,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'featured_image' => $this->featured_image,
            'visibility_settings' => $this->visibility_settings,
            'access_permissions' => $this->access_permissions,
            'template' => $this->template,
            'layout_settings' => $this->layout_settings,
            'custom_fields' => $this->custom_fields,
        ]);

        return true;
    }

    /**
     * Get available change types.
     *
     * @return array
     */
    public static function getChangeTypes(): array
    {
        return [
            self::CHANGE_TYPE_CREATE => 'Created',
            self::CHANGE_TYPE_UPDATE => 'Updated',
            self::CHANGE_TYPE_RESTORE => 'Restored',
            self::CHANGE_TYPE_PUBLISH => 'Published',
            self::CHANGE_TYPE_ARCHIVE => 'Archived',
            self::CHANGE_TYPE_FEATURE => 'Featured',
        ];
    }

    /**
     * Get the size difference from previous version.
     *
     * @return int|null
     */
    public function getSizeDifference(): ?int
    {
        $previousVersion = $this->getPreviousVersion();
        if (!$previousVersion) {
            return null;
        }

        $currentSize = strlen($this->content ?? '');
        $previousSize = strlen($previousVersion->content ?? '');

        return $currentSize - $previousSize;
    }

    /**
     * Get version statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'views' => $this->view_count_snapshot,
            'likes' => $this->like_count_snapshot,
            'shares' => $this->share_count_snapshot,
            'comments' => $this->comment_count_snapshot,
            'rating' => $this->rating_snapshot,
        ];
    }
}