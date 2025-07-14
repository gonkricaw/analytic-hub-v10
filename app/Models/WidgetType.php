<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class WidgetType
 * 
 * Represents a widget type in the Analytics Hub dashboard system.
 * Defines the available widget types and their configurations.
 * 
 * @package App\Models
 */
class WidgetType extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'idbi_widget_types';

    /**
     * The attributes that are mass assignable.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'component',
        'icon',
        'category',
        'is_active',
        'default_width',
        'default_height',
        'min_width',
        'min_height',
        'max_width',
        'max_height',
        'default_refresh_interval',
        'configuration_schema',
        'data_source_types'
    ];

    /**
     * The attributes that should be cast.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'default_width' => 'integer',
        'default_height' => 'integer',
        'min_width' => 'integer',
        'min_height' => 'integer',
        'max_width' => 'integer',
        'max_height' => 'integer',
        'default_refresh_interval' => 'integer',
        'configuration_schema' => 'array',
        'data_source_types' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the widgets for this type.
     */
    public function widgets(): HasMany
    {
        return $this->hasMany(Widget::class);
    }
}