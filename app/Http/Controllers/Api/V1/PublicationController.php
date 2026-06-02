<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\PublicationResource;
use App\Services\PublicationService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Publication',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Laporan Tahunan 2024'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'category', type: 'string', nullable: true, example: 'Laporan'),
        new OA\Property(property: 'file_name', type: 'string', nullable: true),
        new OA\Property(property: 'file_path', type: 'string', nullable: true, description: 'URL download file'),
        new OA\Property(property: 'file_type', type: 'string', nullable: true, example: 'pdf'),
        new OA\Property(property: 'file_size', type: 'string', example: '2.5 MB'),
        new OA\Property(property: 'file_cover', type: 'string', nullable: true, description: 'URL cover image'),
        new OA\Property(property: 'download_count', type: 'integer', example: 120),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class PublicationController extends ApiController
{
    use ApiResponse;

    public function __construct(private PublicationService $service) {}

    #[OA\Get(
        path: '/api/v1/publications',
        summary: 'Daftar publikasi (paginated)',
        tags: ['Publications'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Item per halaman (default: 15)', schema: new OA\Schema(type: 'integer', example: 15)),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Nomor halaman', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
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
                            new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Publication')),
                            new OA\Property(property: 'current_page', type: 'integer', example: 1),
                            new OA\Property(property: 'last_page', type: 'integer', example: 5),
                            new OA\Property(property: 'per_page', type: 'integer', example: 15),
                            new OA\Property(property: 'total', type: 'integer', example: 72),
                        ]
                    ),
                ])
            ),
            new OA\Response(response: 401, description: 'Token tidak valid atau tidak disertakan'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
        ],
        security: [['bearerAuth' => []]],
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $paginator = $this->service->paginate($perPage);

        return $this->success([
            'items'        => PublicationResource::collection($paginator->items()),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/publications/{id}',
        summary: 'Detail publikasi berdasarkan ID',
        tags: ['Publications'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID publikasi', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'OK'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Publication'),
                ])
            ),
            new OA\Response(response: 401, description: 'Token tidak valid atau tidak disertakan'),
            new OA\Response(response: 404, description: 'Publikasi tidak ditemukan'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
        ],
        security: [['bearerAuth' => []]],
    )]
    public function show(int $id): JsonResponse
    {
        try {
            $publication = $this->service->findById($id);
        } catch (ModelNotFoundException) {
            return $this->error('Publikasi tidak ditemukan.', 404);
        }

        return $this->success(new PublicationResource($publication));
    }
}
