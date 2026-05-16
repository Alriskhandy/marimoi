<?php

namespace App\Http\Controllers\Auth;

use Laravel\Passport\Http\Controllers\AccessTokenController as BaseController;
use Laravel\Passport\Token;
use Psr\Http\Message\ServerRequestInterface;

class AccessTokenController extends BaseController
{
    /**
     * Issue token dengan expiry berbeda per tipe client:
     * - Public client : PASSPORT_PUBLIC_TOKEN_EXPIRE_DAYS (default 3 hari)
     * - Admin client  : PASSPORT_ADMIN_TOKEN_EXPIRE_DAYS  (default 7 hari)
     */
    public function issueToken(ServerRequestInterface $request): mixed
    {
        $response = parent::issueToken($request);

        $body    = json_decode($response->getContent(), true);
        $tokenId = $this->extractTokenId($body['access_token'] ?? '');
        $token   = Token::with('client')->find($tokenId);

        if ($token) {
            $clientName = strtolower($token->client->name ?? '');
            $days = str_contains($clientName, 'admin')
                ? (int) env('PASSPORT_ADMIN_TOKEN_EXPIRE_DAYS', 7)
                : (int) env('PASSPORT_PUBLIC_TOKEN_EXPIRE_DAYS', 3);

            $token->expires_at = now()->addDays($days);
            $token->save();

            return response()->json(array_merge($body, [
                'expires_in' => $days * 86400,
            ]));
        }

        return $response;
    }

    private function extractTokenId(string $jwt): string
    {
        $parts   = explode('.', $jwt);
        $payload = json_decode(base64_decode($parts[1] ?? ''), true);
        return $payload['jti'] ?? '';
    }
}
