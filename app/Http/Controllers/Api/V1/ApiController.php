<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'MARIMOI API',
    version: '1.0.0',
    description: 'Manajemen Akselerasi Untuk Monitoring dan Integrasi Wilayah - REST API v1',
)]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'API Server')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
#[OA\Tag(name: 'Layers', description: 'Layer/kategori data spasial')]
#[OA\Tag(name: 'Publications', description: 'Publikasi dan dokumen publik')]
#[OA\Tag(name: 'Statistics', description: 'Statistik pengunjung dan aspirasi')]
abstract class ApiController extends Controller {}
