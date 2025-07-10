<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasUuid;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * SystemConfig Model
 * 
 * Manages system configuration settings for the Analytics Hub system.
 * Handles configuration storage, validation, encryption, and caching.
 * 
 * @property string $id
 * @property string $key
 * @property string $display_name
 * @property string|null $description
 * @property string|null $value
 * @property string|null $default_value
 * @property string $data_type
 * @property string $group
 * @property string|null $category
 * @property int $sort_order
 * @property bool $is_public
 * @property bool $is_editable
 * @property bool $is_system_config
 * @property bool $requires_restart
 * @property array|null $validation_rules
 * @property array|null $options
 * @property string $input_type
 * @property string|null $help_text
 * @property bool $is_active
 * @property bool $is_encrypted
 * @property \Carbon\Carbon|null $last_changed_at
 * @property array|null $environments
 * @property string $deployment_stage
 * @property array|null $metadata
 * @property string $source
 * @property int $version
 * @property string|null $last_changed_by
 * @property string|null $change_reason
 * @property array|null $change_history
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class SystemConfig extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'idbi_system_configs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'display_name',
        'description',
        'value',
        'default_value',
        'data_type',
        'group',
        'category',
        'sort_order',
        'is_public',
        'is_editable',
        'is_system_config',
        'requires_restart',
        'validation_rules',
        'options',
        'input_type',
        'help_text',
        'is_active',
        'is_encrypted',
        'last_changed_at',
        'environments',
        'deployment_stage',
        'metadata',
        'source',
        'version',
        'last_changed_by',
        'change_reason',
        'change_history',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'sort_order' => 'integer',
        'is_public' => 'boolean',
        'is_editable' => 'boolean',
        'is_system_config' => 'boolean',
        'requires_restart' => 'boolean',
        'validation_rules' => 'array',
        'options' => 'array',
        'is_active' => 'boolean',
        'is_encrypted' => 'boolean',
        'last_changed_at' => 'datetime',
        'environments' => 'array',
        'metadata' => 'array',
        'version' => 'integer',
        'change_history' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Data type constants
     */
    const DATA_TYPE_STRING = 'string';
    const DATA_TYPE_INTEGER = 'integer';
    const DATA_TYPE_FLOAT = 'float';
    const DATA_TYPE_BOOLEAN = 'boolean';
    const DATA_TYPE_JSON = 'json';
    const DATA_TYPE_ARRAY = 'array';
    const DATA_TYPE_TEXT = 'text';

    /**
     * Input type constants
     */
    const INPUT_TYPE_TEXT = 'text';
    const INPUT_TYPE_TEXTAREA = 'textarea';
    const INPUT_TYPE_SELECT = 'select';
    const INPUT_TYPE_CHECKBOX = 'checkbox';
    const INPUT_TYPE_RADIO = 'radio';
    const INPUT_TYPE_NUMBER = 'number';
    const INPUT_TYPE_EMAIL = 'email';
    const INPUT_TYPE_PASSWORD = 'password';
    const INPUT_TYPE_URL = 'url';
    const INPUT_TYPE_COLOR = 'color';
    const INPUT_TYPE_DATE = 'date';
    const INPUT_TYPE_TIME = 'time';
    const INPUT_TYPE_DATETIME = 'datetime';

    /**
     * Deployment stage constants
     */
    const STAGE_ALL = 'all';
    const STAGE_DEVELOPMENT = 'development';
    const STAGE_STAGING = 'staging';
    const STAGE_PRODUCTION = 'production';

    /**
     * Source constants
     */
    const SOURCE_DATABASE = 'database';
    const SOURCE_FILE = 'file';
    const SOURCE_ENV = 'env';

    /**
     * Check if configuration is public
     */
    public function isPublic(): bool
    {
        return $this->is_public;
    }

    /**
     * Check if configuration is editable
     */
    public function isEditable(): bool
    {
        return $this->is_editable;
    }

    /**
     * Check if configuration is system config
     */
    public function isSystemConfig(): bool
    {
        return $this->is_system_config;
    }

    /**
     * Check if configuration requires restart
     */
    public function requiresRestart(): bool
    {
        return $this->requires_restart;
    }

    /**
     * Check if configuration is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if configuration value is encrypted
     */
    public function isEncrypted(): bool
    {
        return $this->is_encrypted;
    }

    /**
     * Get the user who last changed this config
     */
    public function lastChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_changed_by');
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
     * Get the configuration value with proper type casting
     */
    public function getTypedValue()
    {
        $value = $this->is_encrypted ? $this->getDecryptedValue() : $this->value;

        if ($value === null) {
            return $this->getTypedDefaultValue();
        }

        return $this->castValue($value, $this->data_type);
    }

    /**
     * Get the default value with proper type casting
     */
    public function getTypedDefaultValue()
    {
        if ($this->default_value === null) {
            return null;
        }

        return $this->castValue($this->default_value, $this->data_type);
    }

    /**
     * Cast value to appropriate type
     */
    protected function castValue($value, string $type)
    {
        switch ($type) {
            case self::DATA_TYPE_INTEGER:
                return (int) $value;
            case self::DATA_TYPE_FLOAT:
                return (float) $value;
            case self::DATA_TYPE_BOOLEAN:
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case self::DATA_TYPE_JSON:
            case self::DATA_TYPE_ARRAY:
                return is_string($value) ? json_decode($value, true) : $value;
            case self::DATA_TYPE_STRING:
            case self::DATA_TYPE_TEXT:
            default:
                return (string) $value;
        }
    }

    /**
     * Set configuration value with encryption if needed
     */
    public function setConfigValue($value): void
    {
        // Convert value to string for storage
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        // Encrypt if needed
        if ($this->is_encrypted) {
            $value = Crypt::encrypt($value);
        }

        // Track change history
        $oldValue = $this->value;
        $history = $this->change_history ?? [];
        $history[] = [
            'old_value' => $oldValue,
            'new_value' => $value,
            'changed_by' => auth()->id(),
            'changed_at' => now()->toISOString(),
            'reason' => $this->change_reason,
        ];

        $this->update([
            'value' => $value,
            'last_changed_at' => now(),
            'last_changed_by' => auth()->id(),
            'change_history' => $history,
            'version' => $this->version + 1,
        ]);

        // Clear cache
        $this->clearCache();
    }

    /**
     * Get decrypted value
     */
    protected function getDecryptedValue(): ?string
    {
        if (!$this->is_encrypted || !$this->value) {
            return $this->value;
        }

        try {
            return Crypt::decrypt($this->value);
        } catch (\Exception $e) {
            // If decryption fails, return null
            return null;
        }
    }

    /**
     * Get configuration value from cache or database
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = "system_config.{$key}";
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $config = self::where('key', $key)->where('is_active', true)->first();
            
            if (!$config) {
                return $default;
            }
            
            return $config->getTypedValue();
        });
    }

    /**
     * Set configuration value
     */
    public static function set(string $key, $value, ?string $reason = null): bool
    {
        $config = self::where('key', $key)->first();
        
        if (!$config) {
            return false;
        }
        
        if (!$config->is_editable) {
            return false;
        }
        
        $config->change_reason = $reason;
        $config->setConfigValue($value);
        
        return true;
    }

    /**
     * Clear configuration cache
     */
    public function clearCache(): void
    {
        Cache::forget("system_config.{$this->key}");
        Cache::forget('system_configs.all');
        Cache::forget("system_configs.group.{$this->group}");
    }

    /**
     * Get all configurations for a group
     */
    public static function getGroup(string $group): array
    {
        $cacheKey = "system_configs.group.{$group}";
        
        return Cache::remember($cacheKey, 3600, function () use ($group) {
            return self::where('group', $group)
                      ->where('is_active', true)
                      ->orderBy('sort_order')
                      ->get()
                      ->mapWithKeys(function ($config) {
                          return [$config->key => $config->getTypedValue()];
                      })
                      ->toArray();
        });
    }

    /**
     * Get public configurations
     */
    public static function getPublicConfigs(): array
    {
        return Cache::remember('system_configs.public', 3600, function () {
            return self::where('is_public', true)
                      ->where('is_active', true)
                      ->orderBy('group')
                      ->orderBy('sort_order')
                      ->get()
                      ->mapWithKeys(function ($config) {
                          return [$config->key => $config->getTypedValue()];
                      })
                      ->toArray();
        });
    }

    /**
     * Validate configuration value
     */
    public function validateValue($value): array
    {
        $errors = [];
        
        if (!$this->validation_rules) {
            return $errors;
        }
        
        $validator = \Validator::make(
            ['value' => $value],
            ['value' => $this->validation_rules],
            [],
            ['value' => $this->display_name]
        );
        
        if ($validator->fails()) {
            $errors = $validator->errors()->get('value');
        }
        
        return $errors;
    }

    /**
     * Create or update configuration
     */
    public static function createOrUpdate(array $data): self
    {
        $config = self::updateOrCreate(
            ['key' => $data['key']],
            array_merge($data, [
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ])
        );
        
        $config->clearCache();
        
        return $config;
    }

    /**
     * Scope: Filter by group
     */
    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Public configurations
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: Editable configurations
     */
    public function scopeEditable(Builder $query): Builder
    {
        return $query->where('is_editable', true);
    }

    /**
     * Scope: System configurations
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system_config', true);
    }

    /**
     * Scope: Active configurations
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Encrypted configurations
     */
    public function scopeEncrypted(Builder $query): Builder
    {
        return $query->where('is_encrypted', true);
    }

    /**
     * Scope: Filter by deployment stage
     */
    public function scopeDeploymentStage(Builder $query, string $stage): Builder
    {
        return $query->where('deployment_stage', $stage)
                    ->orWhere('deployment_stage', self::STAGE_ALL);
    }

    /**
     * Scope: Filter by source
     */
    public function scopeSource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    /**
     * Scope: Requires restart
     */
    public function scopeRequiresRestart(Builder $query): Builder
    {
        return $query->where('requires_restart', true);
    }

    /**
     * Scope: Recently changed
     */
    public function scopeRecentlyChanged(Builder $query, int $days = 7): Builder
    {
        return $query->where('last_changed_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Search configurations
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('key', 'like', "%{$search}%")
              ->orWhere('display_name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('group', 'like', "%{$search}%")
              ->orWhere('category', 'like', "%{$search}%");
        });
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();
        
        // Clear cache when configuration is updated or deleted
        static::updated(function ($config) {
            $config->clearCache();
        });
        
        static::deleted(function ($config) {
            $config->clearCache();
        });
    }
}