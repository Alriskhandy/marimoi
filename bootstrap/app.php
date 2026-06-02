<?php

use App\Http\Middleware\ApiLogger;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TrackVisitor;
use App\Http\Middleware\ValidateApiToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'       => RoleMiddleware::class,
            'api.logger' => ApiLogger::class,
            'api.token'  => ValidateApiToken::class,
        ]);

        $middleware->web(append: [
            TrackVisitor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
