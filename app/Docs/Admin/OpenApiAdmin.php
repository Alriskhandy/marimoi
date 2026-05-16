<?php

namespace App\Docs\Admin;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Marimoi Admin API v1 (Internal)',
    description: 'Admin API — CRUD penuh semua resource. Dokumentasi hanya dapat diakses setelah login ke sistem.'
)]
#[OA\Server(url: 'http://localhost:8000', description: 'Local Server')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'OAuth2 Client Credentials token dari Admin Client.'
)]
class OpenApiAdmin
{
}
