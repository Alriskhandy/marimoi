<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Services\FeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PublicFeatureController extends Controller
{
    public function __construct(private FeatureService $featureService)
    {
    }

    #[OA\Get(
        path: '/api/v1/features',
        tags: ['Features'],
        summary: 'List GIS features (paginated)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'layer_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'year',     in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search',   in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50)),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request): JsonResponse
    {
        $features = $this->featureService->getAllFeatures(
            $request->only(['layer_id', 'year', 'search']),
            min((int) $request->get('per_page', 50), 500)
        );

        return response()->json(['success' => true, 'data' => $features]);
    }

    #[OA\Get(
        path: '/api/v1/features/{id}',
        tags: ['Features'],
        summary: 'Detail feature berdasarkan ID',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 404, description: 'Feature not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $feature = $this->featureService->getFeatureById($id);

        if (!$feature) {
            return response()->json(['success' => false, 'message' => 'Feature not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $feature]);
    }

    #[OA\Get(
        path: '/api/v1/features/uuid/{uuid}',
        tags: ['Features'],
        summary: 'Detail feature berdasarkan UUID',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 404, description: 'Feature not found'),
        ]
    )]
    public function showByUuid(string $uuid): JsonResponse
    {
        $feature = $this->featureService->getFeatureByUuid($uuid);

        if (!$feature) {
            return response()->json(['success' => false, 'message' => 'Feature not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $feature]);
    }

    #[OA\Get(
        path: '/api/v1/features/layer/{layerId}',
        tags: ['Features'],
        summary: 'Semua feature pada satu layer',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'layerId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function getByLayer(int $layerId): JsonResponse
    {
        $features = $this->featureService->getFeaturesByLayer($layerId);

        return response()->json(['success' => true, 'data' => $features]);
    }

    #[OA\Get(
        path: '/api/v1/features/geojson/{layerId}',
        tags: ['Features'],
        summary: 'Feature pada layer dalam format GeoJSON',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'layerId', in: 'path',  required: true,  schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit',   in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 500)),
            new OA\Parameter(name: 'offset',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 0)),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function getGeoJson(int $layerId, Request $request): JsonResponse
    {
        $limit  = min((int) $request->get('limit', 500), 2000);
        $offset = (int) $request->get('offset', 0);

        $features = $this->featureService->getGeoJsonByLayer($layerId, $limit, $offset);
        $total    = $this->featureService->countGeoJsonByLayer($layerId);

        return response()->json([
            'success' => true,
            'data'    => $features,
            'meta'    => ['total' => $total, 'limit' => $limit, 'offset' => $offset],
        ]);
    }
}
