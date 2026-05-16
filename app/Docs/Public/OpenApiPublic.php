<?php

namespace App\Docs\Public;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Marimoi Public API v1',
    description: 'Public API untuk akses data GIS dan submit feedback. Wajib Bearer token dari Public OAuth Client.'
)]
#[OA\Server(url: 'http://localhost:8000', description: 'Local Server')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'OAuth2 Client Credentials token. POST /oauth/token dengan grant_type=client_credentials.'
)]
class OpenApiPublic
{
}
