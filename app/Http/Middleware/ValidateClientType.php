<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\Token;

class ValidateClientType
{
    /**
     * Pastikan token berasal dari client yang sesuai tipenya (public/admin).
     * Mencegah public client mengakses endpoint admin dan sebaliknya.
     */
    public function handle(Request $request, Closure $next, string $type): mixed
    {
        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return response()->json(['message' => 'Token tidak ditemukan'], 401);
        }

        $tokenId     = $this->extractTokenId($bearerToken);
        $accessToken = Token::with('client')->find($tokenId);

        if (!$accessToken) {
            return response()->json(['message' => 'Token tidak valid'], 401);
        }

        $clientName = strtolower($accessToken->client->name ?? '');
        $isAdmin    = str_contains($clientName, 'admin');
        $isPublic   = str_contains($clientName, 'public');

        if ($type === 'admin' && !$isAdmin) {
            return response()->json(['message' => 'Akses ditolak: diperlukan admin client'], 403);
        }

        if ($type === 'public' && !$isPublic) {
            return response()->json(['message' => 'Akses ditolak: diperlukan public client'], 403);
        }

        return $next($request);
    }

    private function extractTokenId(string $jwt): string
    {
        $parts   = explode('.', $jwt);
        $payload = json_decode(base64_decode($parts[1] ?? ''), true);
        return $payload['jti'] ?? '';
    }
}
