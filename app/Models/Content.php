<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\ContentRole;

/**
 * Class Content
 * 
 * Represents content in the Analytics Hub CMS system.
 * Supports hierarchical content structure with encryption capabilities.
 * 
 * @package App\Models
 * 
 * @property string $id UUID primary key
 * @property string $title Content title
 * @property string $slug URL-friendly identifier
 * @property string|null $excerpt Short description
 * @property string $content Main content body
 * @property string $type Content type (page, post, announcement, help, faq, widget)
 * @property string|null $category Content category
 * @property array|null $tags Content tags
 * @property string $status Content status (draft, published, archived, scheduled)
 * @property Carbon|null $published_at Publication date
 * @property Carbon|null $expires_at Expiration date
 * @property bool $is_featured Featured content flag
 * @property string|null $meta_title SEO title
 * @property string|null $meta_description SEO description
 * @property array|null $meta_keywords SEO keywords
 * @property string|null $featured_image Featured image URL
 * @property bool $allow_comments Comments enabled
 * @property bool $is_searchable Include in search
 * @property array|null $visibility_settings Visibility rules
 * @property array|null $access_permissions Access control
 * @property int $view_count View counter
 * @property int $like_count Like counter
 * @property int $comment_count Comment counter
 * @property float|null $rating Average rating
 * @property string|null $parent_id Parent content UUID
 * @property array|null $related_content Related content IDs
 * @property string|null $template Template to use for rendering
 * @property array|null $layout_settings Layout configuration
 * @property array|null $custom_fields Additional custom fields
 * @property string $author_id Content author UUID
 * @property string|null $editor_id Last editor UUID
 * @property string|null $created_by UUID of user who created this record
 * @property string|null $updated_by UUID of user who last updated this record
 * @property Carbon|null $created_at Creation timestamp
 * @property Carbon|null $updated_at Last update timestamp
 * @property Carbon|null $deleted_at Soft deletion timestamp
 */
class Content extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'idbi_contents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'type',
        'category',
        'tags',
        'status',
        'published_at',
        'expires_at',
        'is_featured',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'featured_image',
        'allow_comments',
        'is_searchable',
        'visibility_settings',
        'access_permissions',
        'view_count',
        'like_count',
        'comment_count',
        'rating',
        'parent_id',
        'related_content',
        'template',
        'layout_settings',
        'custom_fields',
        'author_id',
        'editor_id',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_featured' => 'boolean',
            'meta_keywords' => 'array',
            'allow_comments' => 'boolean',
            'is_searchable' => 'boolean',
            'visibility_settings' => 'array',
            'access_permissions' => 'array',
            'view_count' => 'integer',
            'like_count' => 'integer',
            'comment_count' => 'integer',
            'rating' => 'decimal:2',
            'related_content' => 'array',
            'layout_settings' => 'array',
            'custom_fields' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Content type constants
     */
    public const TYPE_PAGE = 'page';
    public const TYPE_POST = 'post';
    public const TYPE_ANNOUNCEMENT = 'announcement';
    public const TYPE_HELP = 'help';
    public const TYPE_FAQ = 'faq';
    public const TYPE_WIDGET = 'widget';

    /**
     * Content status constants
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_SCHEDULED = 'scheduled';

    /**
     * Attributes that should be encrypted
     */
    protected $encrypted = [
        'content',
        'excerpt',
        'custom_fields',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('title') && empty($model->slug)) {
                $model->slug = Str::slug($model->title);
            }
        });
    }

    /**
     * Encrypt sensitive content before saving.
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encrypted) && !is_null($value)) {
            $value = $this->encryptAttribute($value);
        }

        parent::setAttribute($key, $value);
    }

    /**
     * Decrypt sensitive content when retrieving.
     * 
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (in_array($key, $this->encrypted) && !is_null($value)) {
            $value = $this->decryptAttribute($value);
        }

        return $value;
    }

    /**
     * Encrypt an attribute value.
     * 
     * @param mixed $value
     * @return string
     */
    protected function encryptAttribute($value): string
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        return Crypt::encrypt($value);
    }

    /**
     * Decrypt an attribute value.
     * 
     * @param string $value
     * @return mixed
     */
    protected function decryptAttribute(string $value)
    {
        try {
            $decrypted = Crypt::decrypt($value);
            
            // Try to decode as JSON if it looks like JSON
            if (is_string($decrypted) && (str_starts_with($decrypted, '{') || str_starts_with($decrypted, '['))) {
                $json = json_decode($decrypted, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $json;
                }
            }
            
            return $decrypted;
        } catch (\Exception $e) {
            // If decryption fails, return the original value
            return $value;
        }
    }

    /**
     * Check if content is published.
     * 
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && 
               ($this->published_at === null || $this->published_at->isPast()) &&
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Check if content is expired.
     * 
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if content is scheduled.
     * 
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED || 
               ($this->published_at && $this->published_at->isFuture());
    }

    /**
     * Get content author.
     * 
     * @return BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get content editor.
     * 
     * @return BelongsTo
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    /**
     * Get parent content.
     * 
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'parent_id');
    }

    /**
     * Get child contents.
     * 
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Content::class, 'parent_id')
                    ->orderBy('created_at');
    }

    /**
     * Get roles that have access to this content.
     * 
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'idbi_content_roles', 'content_id', 'role_id')
                    ->using(ContentRole::class)
                    ->withPivot([
                        'id', 'is_granted', 'access_type', 'access_conditions', 'restrictions',
                        'can_view', 'can_edit', 'can_delete', 'can_publish', 'can_comment', 'can_share',
                        'is_visible', 'show_in_listings', 'show_metadata', 'allow_download',
                        'assignment_reason', 'assignment_data', 'notes', 'priority',
                        'granted_at', 'expires_at', 'is_temporary', 'duration_hours',
                        'granted_by', 'revoked_by', 'revoked_at', 'revocation_reason',
                        'overrides_default', 'overridden_content_id', 'override_justification',
                        'view_count', 'edit_count', 'last_viewed_at', 'last_edited_at', 'first_accessed_at', 'access_statistics',
                        'comment_count', 'share_count', 'download_count', 'interaction_data',
                        'is_active', 'requires_approval', 'approval_status', 'approved_by', 'approved_at',
                        'is_sensitive', 'requires_justification', 'compliance_notes', 'risk_level', 'audit_access',
                        'workflow_status', 'reviewer_id', 'reviewed_at', 'review_notes',
                        'notify_on_update', 'notify_on_comment', 'notify_on_share',
                        'created_by', 'updated_by'
                    ])
                    ->withTimestamps();
    }

    /**
     * Get content role assignments.
     * 
     * @return HasMany
     */
    public function contentRoles(): HasMany
    {
        return $this->hasMany(ContentRole::class, 'content_id');
    }

    /**
     * Get active content role assignments.
     * 
     * @return HasMany
     */
    public function activeContentRoles(): HasMany
    {
        return $this->hasMany(ContentRole::class, 'content_id')
                    ->active();
    }

    /**
     * Assign a role to this content with specific permissions.
     * 
     * @param string $roleId Role ID to assign
     * @param array $permissions Array of permissions and settings
     * @param string|null $grantedBy User ID who is granting access
     * @return ContentRole|null
     */
    public function assignRole(string $roleId, array $permissions = [], ?string $grantedBy = null): ?ContentRole
    {
        try {
            // Check if assignment already exists
            $existingAssignment = ContentRole::where('content_id', $this->id)
                                            ->where('role_id', $roleId)
                                            ->first();

            if ($existingAssignment) {
                // Update existing assignment
                $existingAssignment->update(array_merge([
                    'is_granted' => true,
                    'is_active' => true,
                    'granted_by' => $grantedBy ?? Auth::id(),
                    'granted_at' => now(),
                    'revoked_by' => null,
                    'revoked_at' => null,
                    'revocation_reason' => null
                ], $permissions));

                return $existingAssignment;
            }

            // Create new assignment
            $defaultPermissions = [
                'is_granted' => true,
                'access_type' => ContentRole::ACCESS_VIEW,
                'can_view' => true,
                'can_edit' => false,
                'can_delete' => false,
                'can_publish' => false,
                'can_comment' => false,
                'can_share' => false,
                'is_visible' => true,
                'show_in_listings' => true,
                'show_metadata' => true,
                'allow_download' => false,
                'priority' => ContentRole::PRIORITY_NORMAL,
                'is_active' => true,
                'granted_by' => $grantedBy ?? Auth::id(),
                'granted_at' => now(),
                'risk_level' => ContentRole::RISK_LOW
            ];

            $assignmentData = array_merge($defaultPermissions, $permissions, [
                'content_id' => $this->id,
                'role_id' => $roleId
            ]);

            return ContentRole::create($assignmentData);
        } catch (\Exception $e) {
            Log::error('Failed to assign role to content', [
                'content_id' => $this->id,
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Remove a role assignment from this content.
     * 
     * @param string $roleId Role ID to remove
     * @param string|null $revokedBy User ID who is revoking access
     * @param string|null $reason Reason for revocation
     * @return bool
     */
    public function removeRole(string $roleId, ?string $revokedBy = null, ?string $reason = null): bool
    {
        try {
            $assignment = ContentRole::where('content_id', $this->id)
                                    ->where('role_id', $roleId)
                                    ->first();

            if (!$assignment) {
                return false;
            }

            return $assignment->revokeAccess($revokedBy, $reason);
        } catch (\Exception $e) {
            Log::error('Failed to remove role from content', [
                'content_id' => $this->id,
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if a role has access to this content.
     * 
     * @param string $roleId Role ID to check
     * @return bool
     */
    public function hasRoleAccess(string $roleId): bool
    {
        return ContentRole::where('content_id', $this->id)
                         ->where('role_id', $roleId)
                         ->active()
                         ->exists();
    }

    /**
     * Check if a user has access to this content through their roles.
     * 
     * @param User $user User to check
     * @param string $permission Specific permission to check (view, edit, etc.)
     * @return bool
     */
    public function userHasAccess(User $user, string $permission = 'view'): bool
    {
        $userRoleIds = $user->roles()->pluck('idbi_roles.id')->toArray();
        
        if (empty($userRoleIds)) {
            return false;
        }

        $permissionColumn = 'can_' . $permission;
        
        return ContentRole::where('content_id', $this->id)
                         ->whereIn('role_id', $userRoleIds)
                         ->active()
                         ->where($permissionColumn, true)
                         ->exists();
    }

    /**
     * Get all roles with their permissions for this content.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRolePermissions()
    {
        return ContentRole::with('role')
                         ->where('content_id', $this->id)
                         ->active()
                         ->get();
    }

    /**
     * Bulk assign multiple roles to this content.
     * 
     * @param array $roleAssignments Array of role assignments with permissions
     * @param string|null $grantedBy User ID who is granting access
     * @return array Array of created/updated assignments
     */
    public function bulkAssignRoles(array $roleAssignments, ?string $grantedBy = null): array
    {
        $results = [];
        
        foreach ($roleAssignments as $roleId => $permissions) {
            $assignment = $this->assignRole($roleId, $permissions, $grantedBy);
            if ($assignment) {
                $results[] = $assignment;
            }
        }
        
        return $results;
    }

    /**
     * Get user activities related to this content.
     * 
     * @return HasMany
     */
    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class, 'subject_id')
                    ->where('subject_type', self::class);
    }

    /**
     * Increment view count.
     * 
     * @return void
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Increment like count.
     * 
     * @return void
     */
    public function incrementLikeCount(): void
    {
        $this->increment('like_count');
    }

    /**
     * Decrement like count.
     * 
     * @return void
     */
    public function decrementLikeCount(): void
    {
        $this->decrement('like_count');
    }

    /**
     * Get content excerpt or generate from content.
     * 
     * @param int $length
     * @return string
     */
    public function getExcerptAttribute($value = null): string
    {
        if ($value) {
            return $value;
        }

        return Str::limit(strip_tags($this->content), 150);
    }

    /**
     * Get reading time estimate in minutes.
     * 
     * @return int
     */
    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        return max(1, ceil($wordCount / 200)); // Assuming 200 words per minute
    }

    /**
     * Scope for published content.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
                    ->where(function ($q) {
                        $q->whereNull('published_at')
                          ->orWhere('published_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for featured content.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for content by type.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for content by category.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for searchable content.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchable($query)
    {
        return $query->where('is_searchable', true);
    }

    /**
     * Scope for content by author.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $authorId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAuthor($query, string $authorId)
    {
        return $query->where('author_id', $authorId);
    }

    /**
     * Scope for popular content (by view count).
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('view_count', 'desc')->limit($limit);
    }

    /**
     * Scope for recent content.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days))
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Scope for content with tags.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $tags
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithTags($query, array $tags)
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    // ==========================================
    // CONTENT EXPIRY FUNCTIONALITY
    // ==========================================

    /**
     * Scope for expired content.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
    }

    /**
     * Scope for content expiring soon.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days Number of days to look ahead
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '>', now())
                    ->where('expires_at', '<=', now()->addDays($days));
    }

    /**
     * Scope for content expiring on a specific date.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date Date in Y-m-d format
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiringOn($query, string $date)
    {
        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();
        
        return $query->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [$startOfDay, $endOfDay]);
    }

    /**
     * Scope for content without expiry date.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeverExpires($query)
    {
        return $query->whereNull('expires_at');
    }

    /**
     * Check if content will expire within specified days.
     * 
     * @param int $days Number of days to check
     * @return bool
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isFuture() && 
               $this->expires_at->lte(now()->addDays($days));
    }

    /**
     * Get days until expiry.
     * 
     * @return int|null Number of days until expiry, null if no expiry date
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Get human-readable time until expiry.
     * 
     * @return string|null
     */
    public function getTimeUntilExpiry(): ?string
    {
        if (!$this->expires_at) {
            return null;
        }

        if ($this->isExpired()) {
            return 'Expired ' . $this->expires_at->diffForHumans();
        }

        return 'Expires ' . $this->expires_at->diffForHumans();
    }

    /**
     * Extend content expiry date.
     * 
     * @param int $days Number of days to extend
     * @param string|null $reason Reason for extension
     * @param string|null $extendedBy User ID who extended
     * @return bool
     */
    public function extendExpiry(int $days, ?string $reason = null, ?string $extendedBy = null): bool
    {
        try {
            $originalExpiry = $this->expires_at;
            $newExpiry = $originalExpiry ? 
                        $originalExpiry->addDays($days) : 
                        now()->addDays($days);

            $this->update([
                'expires_at' => $newExpiry,
                'editor_id' => $extendedBy ?? Auth::id()
            ]);

            // Log the extension
            activity()
                ->performedOn($this)
                ->withProperties([
                    'action' => 'expiry_extended',
                    'original_expiry' => $originalExpiry?->toISOString(),
                    'new_expiry' => $newExpiry->toISOString(),
                    'days_extended' => $days,
                    'reason' => $reason,
                    'extended_by' => $extendedBy ?? Auth::id()
                ])
                ->log("Content expiry extended by {$days} days");

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to extend content expiry', [
                'content_id' => $this->id,
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Remove expiry date from content.
     * 
     * @param string|null $reason Reason for removing expiry
     * @param string|null $removedBy User ID who removed expiry
     * @return bool
     */
    public function removeExpiry(?string $reason = null, ?string $removedBy = null): bool
    {
        try {
            $originalExpiry = $this->expires_at;

            $this->update([
                'expires_at' => null,
                'editor_id' => $removedBy ?? Auth::id()
            ]);

            // Log the removal
            activity()
                ->performedOn($this)
                ->withProperties([
                    'action' => 'expiry_removed',
                    'original_expiry' => $originalExpiry?->toISOString(),
                    'reason' => $reason,
                    'removed_by' => $removedBy ?? Auth::id()
                ])
                ->log('Content expiry date removed');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove content expiry', [
                'content_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Set new expiry date for content.
     * 
     * @param Carbon|string $expiryDate New expiry date
     * @param string|null $reason Reason for setting expiry
     * @param string|null $setBy User ID who set expiry
     * @return bool
     */
    public function setExpiry($expiryDate, ?string $reason = null, ?string $setBy = null): bool
    {
        try {
            $originalExpiry = $this->expires_at;
            $newExpiry = $expiryDate instanceof Carbon ? $expiryDate : Carbon::parse($expiryDate);

            $this->update([
                'expires_at' => $newExpiry,
                'editor_id' => $setBy ?? Auth::id()
            ]);

            // Log the change
            activity()
                ->performedOn($this)
                ->withProperties([
                    'action' => 'expiry_set',
                    'original_expiry' => $originalExpiry?->toISOString(),
                    'new_expiry' => $newExpiry->toISOString(),
                    'reason' => $reason,
                    'set_by' => $setBy ?? Auth::id()
                ])
                ->log('Content expiry date updated');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to set content expiry', [
                'content_id' => $this->id,
                'expiry_date' => $expiryDate,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Get expiry status with detailed information.
     * 
     * @return array
     */
    public function getExpiryStatus(): array
    {
        if (!$this->expires_at) {
            return [
                'has_expiry' => false,
                'status' => 'never_expires',
                'message' => 'This content never expires',
                'days_remaining' => null,
                'expires_at' => null,
                'is_expired' => false,
                'is_expiring_soon' => false
            ];
        }

        $isExpired = $this->isExpired();
        $daysRemaining = $this->getDaysUntilExpiry();
        $isExpiringSoon = $this->isExpiringSoon();

        if ($isExpired) {
            $status = 'expired';
            $message = 'This content has expired';
        } elseif ($isExpiringSoon) {
            $status = 'expiring_soon';
            $message = "This content expires in {$daysRemaining} day(s)";
        } else {
            $status = 'active';
            $message = "This content expires in {$daysRemaining} day(s)";
        }

        return [
            'has_expiry' => true,
            'status' => $status,
            'message' => $message,
            'days_remaining' => $daysRemaining,
            'expires_at' => $this->expires_at->toISOString(),
            'expires_at_human' => $this->expires_at->format('M j, Y \a\t g:i A'),
            'is_expired' => $isExpired,
            'is_expiring_soon' => $isExpiringSoon,
            'time_until_expiry' => $this->getTimeUntilExpiry()
        ];
    }

    /**
     * Get content expiry statistics for reporting.
     * 
     * @return array
     */
    public static function getExpiryStatistics(): array
    {
        $total = static::count();
        $withExpiry = static::whereNotNull('expires_at')->count();
        $expired = static::expired()->count();
        $expiringSoon = static::expiringSoon(7)->count();
        $expiringThisMonth = static::expiringSoon(30)->count();
        $neverExpires = static::neverExpires()->count();

        return [
            'total_content' => $total,
            'with_expiry_date' => $withExpiry,
            'expired' => $expired,
            'expiring_within_7_days' => $expiringSoon,
            'expiring_within_30_days' => $expiringThisMonth,
            'never_expires' => $neverExpires,
            'percentage_with_expiry' => $total > 0 ? round(($withExpiry / $total) * 100, 1) : 0,
            'percentage_expired' => $withExpiry > 0 ? round(($expired / $withExpiry) * 100, 1) : 0
        ];
    }
}