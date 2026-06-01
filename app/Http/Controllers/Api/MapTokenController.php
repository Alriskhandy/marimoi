<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;

class MapTokenController extends Controller
{
    public function __construct(private AuthorizationServer $server)
    {
    }

    public function __invoke(ServerRequestInterface $psrRequest): JsonResponse
    {
        $psrRequest = $psrRequest->withParsedBody([
            'grant_type'    => 'client_credentials',
            'client_id'     => env('OAUTH_ADMIN_CLIENT_ID'),
            'client_secret' => env('OAUTH_ADMIN_CLIENT_SECRET'),
            'scope'         => 'admin.layers admin.features',
        ]);

        try {
            $psr = $this->server->respondToAccessTokenRequest($psrRequest, new Psr7Response);

            // League menulis body ke stream — rewind dulu sebelum baca
            $psr->getBody()->rewind();
            $body = json_decode($psr->getBody()->getContents(), true);

            if (empty($body['access_token'])) {
                return response()->json(['error' => 'Token unavailable', 'detail' => $body], 503);
            }

            return response()->json([
                'access_token' => $body['access_token'],
                'expires_in'   => $body['expires_in'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
    }
}
