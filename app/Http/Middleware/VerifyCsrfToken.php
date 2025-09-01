<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'visitors',
    ];
    // protected $except = [
    //     'api/track-visitor',
    //     '/api/track-visitor',
    //     'api/*',  // Exclude all API routes
    // ];
}