<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\FeatureController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminFeatureController extends FeatureController
{
    #[OA\Get(
        path: '/api/v1/admin/features',
        tags: ['Admin Features'],
        summary: 'List features',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(
        path: '/api/v1/admin/features',
        tags: ['Admin Features'],
        summary: 'Buat feature baru',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function store(Request $request): JsonResponse
    {
        return parent::store($request);
    }

    #[OA\Get(
        path: '/api/v1/admin/features/{id}',
        tags: ['Admin Features'],
        summary: 'Detail feature',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function show(int $id): JsonResponse
    {
        return parent::show($id);
    }

    #[OA\Put(
        path: '/api/v1/admin/features/{id}',
        tags: ['Admin Features'],
        summary: 'Update feature',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        return parent::update($request, $id);
    }

    #[OA\Delete(
        path: '/api/v1/admin/features/{id}',
        tags: ['Admin Features'],
        summary: 'Hapus feature',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }

    #[OA\Get(
        path: '/api/v1/admin/features/statistics',
        tags: ['Admin Features'],
        summary: 'Statistik feature',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function getStatistics(): JsonResponse
    {
        return parent::getStatistics();
    }

    #[OA\Post(
        path: '/api/v1/admin/features/bulk',
        tags: ['Admin Features'],
        summary: 'Bulk create features',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function bulkStore(Request $request): JsonResponse
    {
        return parent::bulkStore($request);
    }

    #[OA\Get(
        path: '/api/v1/admin/features/geojson/{layerId}',
        tags: ['Admin Features'],
        summary: 'GeoJSON features untuk layer (dipakai peta)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'layerId', in: 'path',  required: true,  schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit',   in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 500)),
            new OA\Parameter(name: 'offset',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 0)),
        ],
        responses: [new OA\Response(response: 200, description: 'FeatureCollection GeoJSON')]
    )]
    public function getGeoJsonByLayer(int $layerId, Request $request): JsonResponse
    {
        return parent::getGeoJsonByLayer($layerId, $request);
    }
}
