<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class WidgetConfiguration
 * 
 * Represents widget configuration settings in the Analytics Hub dashboard system.
 * Manages individual widget settings and customizations.
 * 
 * @package App\Models
 */
class WidgetConfiguration extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'idbi_widget_configurations';

    /**
     * The attributes that are mass assignable.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'widget_id',
        'key',
        'value',
        'type',
        'is_encrypted',
        'description',
        'validation_rules',
        'default_value'
    ];

    /**
     * The attributes that should be cast.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_encrypted' => 'boolean',
        'validation_rules' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the widget that owns the configuration.
     */
    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }
}