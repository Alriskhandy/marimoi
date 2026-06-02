<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\WebStatisticService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class WebStatisticController extends ApiController
{
    use ApiResponse;

    public function __construct(private WebStatisticService $service) {}

    #[OA\Get(
        path: '/api/v1/web-statistics',
        summary: 'Statistik pengunjung dan aspirasi website',
        tags: ['Statistics'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'OK'),
                    new OA\Property(
                        property: 'data',
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'visitors',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'today', type: 'integer', example: 45),
                                    new OA\Property(property: 'week', type: 'integer', example: 312),
                                    new OA\Property(property: 'month', type: 'integer', example: 1250),
                                    new OA\Property(property: 'year', type: 'integer', example: 14800),
                                    new OA\Property(property: 'total', type: 'integer', example: 52000),
                                ]
                            ),
                            new OA\Property(
                                property: 'aspirations',
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'kritik_dan_saran', type: 'integer', example: 87),
                                    new OA\Property(property: 'usulan', type: 'integer', example: 214),
                                ]
                            ),
                        ]
                    ),
                ])
            ),
            new OA\Response(response: 401, description: 'Token tidak valid atau tidak disertakan'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
        ],
        security: [['bearerAuth' => []]],
    )]
    public function index(): JsonResponse
    {
        return $this->success($this->service->getData());
    }
}
