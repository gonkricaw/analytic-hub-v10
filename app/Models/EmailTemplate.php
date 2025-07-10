<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasUuid;
use Illuminate\Support\Facades\Crypt;

/**
 * EmailTemplate Model
 * 
 * Manages email templates for the Analytics Hub system.
 * Handles template content, configuration, versioning, and usage tracking.
 * 
 * @property string $id
 * @property string $name
 * @property string $display_name
 * @property string|null $description
 * @property string $subject
 * @property string $body_html
 * @property string|null $body_text
 * @property string $category
 * @property string $type
 * @property string|null $event_trigger
 * @property bool $is_active
 * @property bool $is_system_template
 * @property array|null $variables
 * @property array|null $default_data
 * @property string|null $from_email
 * @property string|null $from_name
 * @property string|null $reply_to
 * @property array|null $cc_emails
 * @property array|null $bcc_emails
 * @property string $language
 * @property int $priority
 * @property array|null $attachments
 * @property array|null $headers
 * @property string $version
 * @property string|null $parent_template_id
 * @property bool $is_current_version
 * @property int $usage_count
 * @property \Carbon\Carbon|null $last_used_at
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class EmailTemplate extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'idbi_email_templates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'subject',
        'body_html',
        'body_text',
        'category',
        'type',
        'event_trigger',
        'is_active',
        'is_system_template',
        'variables',
        'default_data',
        'from_email',
        'from_name',
        'reply_to',
        'cc_emails',
        'bcc_emails',
        'language',
        'priority',
        'attachments',
        'headers',
        'version',
        'parent_template_id',
        'is_current_version',
        'usage_count',
        'last_used_at',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_system_template' => 'boolean',
        'variables' => 'array',
        'default_data' => 'array',
        'cc_emails' => 'array',
        'bcc_emails' => 'array',
        'attachments' => 'array',
        'headers' => 'array',
        'priority' => 'integer',
        'is_current_version' => 'boolean',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Template type constants
     */
    const TYPE_SYSTEM = 'system';
    const TYPE_USER = 'user';
    const TYPE_AUTOMATED = 'automated';

    /**
     * Template category constants
     */
    const CATEGORY_AUTHENTICATION = 'authentication';
    const CATEGORY_NOTIFICATION = 'notification';
    const CATEGORY_REPORT = 'report';
    const CATEGORY_MARKETING = 'marketing';
    const CATEGORY_SYSTEM = 'system';

    /**
     * Email priority constants
     */
    const PRIORITY_HIGH = 1;
    const PRIORITY_NORMAL = 3;
    const PRIORITY_LOW = 5;

    /**
     * Language constants
     */
    const LANGUAGE_INDONESIAN = 'id';
    const LANGUAGE_ENGLISH = 'en';

    /**
     * Check if template is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if template is system protected
     */
    public function isSystemTemplate(): bool
    {
        return $this->is_system_template;
    }

    /**
     * Check if template is current version
     */
    public function isCurrentVersion(): bool
    {
        return $this->is_current_version;
    }

    /**
     * Check if template has high priority
     */
    public function isHighPriority(): bool
    {
        return $this->priority === self::PRIORITY_HIGH;
    }

    /**
     * Get the parent template relationship
     */
    public function parentTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'parent_template_id');
    }

    /**
     * Get the child templates relationship
     */
    public function childTemplates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class, 'parent_template_id');
    }

    /**
     * Get the creator relationship
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater relationship
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the email queue entries relationship
     */
    public function emailQueues(): HasMany
    {
        return $this->hasMany(EmailQueue::class, 'template_id');
    }

    /**
     * Increment usage count and update last used timestamp
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get template with variables replaced
     */
    public function renderTemplate(array $data = []): array
    {
        $mergedData = array_merge($this->default_data ?? [], $data);
        
        $subject = $this->replaceVariables($this->subject, $mergedData);
        $bodyHtml = $this->replaceVariables($this->body_html, $mergedData);
        $bodyText = $this->body_text ? $this->replaceVariables($this->body_text, $mergedData) : null;
        
        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
        ];
    }

    /**
     * Replace variables in template content
     */
    protected function replaceVariables(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
            $content = str_replace('{{ ' . $key . ' }}', $value, $content);
        }
        
        return $content;
    }

    /**
     * Create new version of template
     */
    public function createNewVersion(array $attributes = []): EmailTemplate
    {
        // Mark current version as not current
        $this->update(['is_current_version' => false]);
        
        // Create new version
        $newVersion = $this->replicate();
        $newVersion->parent_template_id = $this->id;
        $newVersion->is_current_version = true;
        $newVersion->usage_count = 0;
        $newVersion->last_used_at = null;
        
        // Update version number
        $versionParts = explode('.', $this->version);
        $versionParts[1] = (int)$versionParts[1] + 1;
        $newVersion->version = implode('.', $versionParts);
        
        // Apply new attributes
        $newVersion->fill($attributes);
        $newVersion->save();
        
        return $newVersion;
    }

    /**
     * Get encrypted body content
     */
    public function getEncryptedBodyHtmlAttribute(): string
    {
        return Crypt::encrypt($this->attributes['body_html']);
    }

    /**
     * Set encrypted body content
     */
    public function setBodyHtmlAttribute($value): void
    {
        $this->attributes['body_html'] = $value;
    }

    /**
     * Scope: Active templates
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: System templates
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system_template', true);
    }

    /**
     * Scope: User templates
     */
    public function scopeUser(Builder $query): Builder
    {
        return $query->where('is_system_template', false);
    }

    /**
     * Scope: Current versions only
     */
    public function scopeCurrentVersion(Builder $query): Builder
    {
        return $query->where('is_current_version', true);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter by language
     */
    public function scopeLanguage(Builder $query, string $language): Builder
    {
        return $query->where('language', $language);
    }

    /**
     * Scope: Filter by event trigger
     */
    public function scopeEventTrigger(Builder $query, string $eventTrigger): Builder
    {
        return $query->where('event_trigger', $eventTrigger);
    }

    /**
     * Scope: Most used templates
     */
    public function scopeMostUsed(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * Scope: Recently used templates
     */
    public function scopeRecentlyUsed(Builder $query, int $days = 30): Builder
    {
        return $query->where('last_used_at', '>=', now()->subDays($days))
                    ->orderBy('last_used_at', 'desc');
    }

    /**
     * Scope: High priority templates
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->where('priority', self::PRIORITY_HIGH);
    }

    /**
     * Scope: Search templates
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('display_name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('subject', 'like', "%{$search}%");
        });
    }
}