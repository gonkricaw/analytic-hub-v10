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
use Illuminate\Support\Str;

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
                    ->withTimestamps();
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
}