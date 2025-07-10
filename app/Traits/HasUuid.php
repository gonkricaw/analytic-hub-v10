<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait HasUuid
 * 
 * Provides UUID functionality for Eloquent models.
 * Automatically generates UUID for primary key when creating new records.
 * 
 * @package App\Traits
 */
trait HasUuid
{
    /**
     * Boot the UUID trait for the model.
     * 
     * @return void
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     * 
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     * 
     * @return string
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}