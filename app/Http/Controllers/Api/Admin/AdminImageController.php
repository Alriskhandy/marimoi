<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\FeatureImageController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminImageController extends FeatureImageController
{
    #[OA\Get(
        path: '/api/v1/admin/images',
        tags: ['Admin Images'],
        summary: 'List images',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(
        path: '/api/v1/admin/images',
        tags: ['Admin Images'],
        summary: 'Buat metadata image',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function store(Request $request): JsonResponse
    {
        return parent::store($request);
    }

    #[OA\Get(
        path: '/api/v1/admin/images/{id}',
        tags: ['Admin Images'],
        summary: 'Detail image',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function show(int $id): JsonResponse
    {
        return parent::show($id);
    }

    #[OA\Put(
        path: '/api/v1/admin/images/{id}',
        tags: ['Admin Images'],
        summary: 'Update image',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        return parent::update($request, $id);
    }

    #[OA\Delete(
        path: '/api/v1/admin/images/{id}',
        tags: ['Admin Images'],
        summary: 'Hapus image',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }

    #[OA\Post(
        path: '/api/v1/admin/images/upload/{featureId}',
        tags: ['Admin Images'],
        summary: 'Upload image untuk feature',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'featureId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function upload(Request $request, int $featureId): JsonResponse
    {
        return parent::upload($request, $featureId);
    }

    #[OA\Post(
        path: '/api/v1/admin/images/bulk-upload/{featureId}',
        tags: ['Admin Images'],
        summary: 'Bulk upload image untuk feature',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'featureId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function bulkUpload(Request $request, int $featureId): JsonResponse
    {
        return parent::bulkUpload($request, $featureId);
    }
}
