<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

/**
 * Class TrimStrings
 * 
 * Middleware that automatically trims whitespace from string inputs.
 * Helps maintain data consistency and prevents issues with leading/trailing spaces.
 * 
 * @package App\Http\Middleware
 */
class TrimStrings extends Middleware
{
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array<int, string>
     */
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
    ];
}