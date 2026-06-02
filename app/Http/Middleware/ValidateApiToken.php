<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->bearerToken();

        if (!$raw) {
            return response()->json([
                'success' => false,
                'message' => 'API token diperlukan. Sertakan header: Authorization: Bearer <token>',
                'data'    => null,
            ], 401);
        }

        $hashed = hash('sha256', $raw);
        $token  = ApiToken::where('token', $hashed)->isValid()->first();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sudah kedaluwarsa.',
                'data'    => null,
            ], 401);
        }

        $token->update(['last_used_at' => now()]);

        return $next($request);
    }
}
