<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\LayerController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminLayerController extends LayerController
{
    #[OA\Get(
        path: '/api/v1/admin/layers',
        tags: ['Admin Layers'],
        summary: 'List layers',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(
        path: '/api/v1/admin/layers',
        tags: ['Admin Layers'],
        summary: 'Buat layer baru',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function store(Request $request): JsonResponse
    {
        return parent::store($request);
    }

    #[OA\Get(
        path: '/api/v1/admin/layers/{id}',
        tags: ['Admin Layers'],
        summary: 'Detail layer',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function show(int $id): JsonResponse
    {
        return parent::show($id);
    }

    #[OA\Put(
        path: '/api/v1/admin/layers/{id}',
        tags: ['Admin Layers'],
        summary: 'Update layer',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        return parent::update($request, $id);
    }

    #[OA\Delete(
        path: '/api/v1/admin/layers/{id}',
        tags: ['Admin Layers'],
        summary: 'Hapus layer',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }

    #[OA\Get(
        path: '/api/v1/admin/layers/tree',
        tags: ['Admin Layers'],
        summary: 'Hierarki layer (tree)',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function getTree(): JsonResponse
    {
        return $this->getLayerTree();
    }

    #[OA\Get(
        path: '/api/v1/admin/layers/statistics',
        tags: ['Admin Layers'],
        summary: 'Statistik layer',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function getStatistics(): JsonResponse
    {
        return parent::getStatistics();
    }

    #[OA\Post(
        path: '/api/v1/admin/layers/{id}/toggle-active',
        tags: ['Admin Layers'],
        summary: 'Toggle aktif/non-aktif layer',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function toggleActive(int $id): JsonResponse
    {
        return parent::toggleActive($id);
    }
}
