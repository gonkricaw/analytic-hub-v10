<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ValidateSignature as Middleware;

/**
 * Class ValidateSignature
 * 
 * Middleware that validates signed URLs to ensure they haven't been tampered with.
 * Used for secure links like password reset, email verification, etc.
 * 
 * @package App\Http\Middleware
 */
class ValidateSignature extends Middleware
{
    /**
     * The names of the query string parameters that should be ignored.
     *
     * @var array<int, string>
     */
    protected $except = [
        // 'fbclid',
        // 'utm_campaign',
        // 'utm_content',
        // 'utm_medium',
        // 'utm_source',
        // 'utm_term',
    ];
}