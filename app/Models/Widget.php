<?php

namespace App\Models;

use App\Models\User;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class Widget
 * 
 * Represents a widget in the Analytics Hub dashboard system.
 * Manages widget configuration, data sources, and rendering.
 * 
 * @package App\Models
 */
class Widget extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'idbi_widgets';

    /**
     * The attributes that are mass assignable.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'title',
        'description',
        'widget_type_id',
        'dashboard_id',
        'created_by',
        'position_x',
        'position_y',
        'width',
        'height',
        'is_active',
        'refresh_interval',
        'settings',
        'data_source',
        'sort_order'
    ];

    /**
     * The attributes that should be cast.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'refresh_interval' => 'integer',
        'sort_order' => 'integer',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the dashboard that owns the widget.
     */
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    /**
     * Get the widget type.
     */
    public function widgetType(): BelongsTo
    {
        return $this->belongsTo(WidgetType::class);
    }

    /**
     * Get the widget configurations.
     */
    public function configurations(): HasMany
    {
        return $this->hasMany(WidgetConfiguration::class);
    }

    /**
     * Get the user who created the widget.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}