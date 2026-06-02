<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        Log::channel('api')->info('API Request', [
            'method'      => $request->method(),
            'url'         => $request->fullUrl(),
            'ip'          => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'request'     => $request->except(['password', 'token', 'secret']),
            'status'      => $response->getStatusCode(),
            'duration_ms' => (int) ((microtime(true) - LARAVEL_START) * 1000),
        ]);

        return $response;
    }
}
