<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\LayerResource;
use App\Services\LayerService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Layer',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'type', type: 'string', example: 'tematik'),
        new OA\Property(property: 'name', type: 'string', example: 'Infrastruktur'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'color', type: 'string', nullable: true, example: '#FF5733'),
        new OA\Property(property: 'icon', type: 'string', nullable: true),
        new OA\Property(property: 'is_marker', type: 'boolean', example: false),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'img_path', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'child', type: 'array', items: new OA\Items(ref: '#/components/schemas/Layer')),
    ]
)]
class LayerController extends ApiController
{
    use ApiResponse;

    // Tipe layer yang valid
    private const VALID_TYPES = ['tematik', 'usulan_musrenbang', 'pokir_dprd', 'psd', 'psn'];

    public function __construct(private LayerService $service) {}

    #[OA\Get(
        path: '/api/v1/layers',
        summary: 'Daftar semua layer (hierarki)',
        description: 'Mengembalikan root layer beserta child-nya. Filter opsional by type. Di-cache 60 menit per kombinasi filter.',
        tags: ['Layers'],
        parameters: [
            new OA\Parameter(
                name: 'type',
                in: 'query',
                required: false,
                description: 'Filter berdasarkan tipe layer. Nilai yang valid: tematik, usulan_musrenbang, pokir_dprd, psd, psn',
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['tematik', 'usulan_musrenbang', 'pokir_dprd', 'psd', 'psn'],
                    example: 'tematik'
                )
            ),
        ],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'OK'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Layer')),
                ])
            ),
            new OA\Response(response: 401, description: 'Token tidak valid atau tidak disertakan'),
            new OA\Response(response: 422, description: 'Tipe tidak valid'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');

        if ($type && !in_array($type, self::VALID_TYPES)) {
            return $this->error(
                'Tipe tidak valid. Gunakan salah satu: ' . implode(', ', self::VALID_TYPES),
                422
            );
        }

        $layers = $this->service->getTree($type ?: null);

        return $this->success(LayerResource::collection($layers));
    }

    #[OA\Get(
        path: '/api/v1/layers/{id}',
        summary: 'Detail layer berdasarkan ID',
        tags: ['Layers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID layer', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'OK'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Layer'),
                ])
            ),
            new OA\Response(response: 401, description: 'Token tidak valid atau tidak disertakan'),
            new OA\Response(response: 404, description: 'Layer tidak ditemukan'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        try {
            $layer = $this->service->findById($id);
        } catch (ModelNotFoundException) {
            return $this->error('Layer tidak ditemukan.', 404);
        }

        return $this->success(new LayerResource($layer));
    }
}
