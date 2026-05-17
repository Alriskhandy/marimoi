<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TrackVisitor;
use App\Http\Middleware\ValidateClientType;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Passport\Http\Middleware\CheckClientCredentials;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'        => RoleMiddleware::class,
            'client'      => CheckClientCredentials::class,
            'client.type' => ValidateClientType::class,
        ]);

        $middleware->web(append: [
            TrackVisitor::class,
        ]);

        // Kecualikan token endpoint dari CSRF — ini route machine-to-machine
        $middleware->validateCsrfTokens(except: ['/oauth/token']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Rute api/* selalu kembalikan JSON — cegah redirect ke /login
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $_e) {
            return $request->is('api/*');
        });
    })->create();
