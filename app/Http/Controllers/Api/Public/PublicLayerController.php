<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Layer;
use App\Services\LayerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PublicLayerController extends Controller
{
    public function __construct(private LayerService $layerService)
    {
    }

    #[OA\Get(
        path: '/api/v1/layers',
        tags: ['Layers'],
        summary: 'List GIS layers',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'type',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category',  in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'parent_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'search',    in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page',  in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50)),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request): JsonResponse
    {
        // Query langsung tanpa eager-load `features` agar response ringan.
        // Untuk feature per layer, pakai /api/v1/features/layer/{layerId}.
        $query = Layer::query()->with(['parent:id,name', 'children:id,name,parent_id']);

        foreach (['type', 'category', 'is_active'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->get($field));
            }
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->get('parent_id'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'ILIKE', '%' . $request->get('search') . '%');
        }

        $perPage = min((int) $request->get('per_page', 50), 200);
        $layers  = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json(['success' => true, 'data' => $layers]);
    }

    #[OA\Get(
        path: '/api/v1/layers/{id}',
        tags: ['Layers'],
        summary: 'Detail layer',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 404, description: 'Layer not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        // Tanpa eager-load `features` agar response ringan.
        $layer = Layer::with(['parent:id,name', 'children:id,name,parent_id'])->find($id);

        if (!$layer) {
            return response()->json(['success' => false, 'message' => 'Layer not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $layer]);
    }

    #[OA\Get(
        path: '/api/v1/layers/tree',
        tags: ['Layers'],
        summary: 'Hierarki layer (tree)',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function getTree(): JsonResponse
    {
        $tree = $this->layerService->getLayerTree();

        return response()->json(['success' => true, 'data' => $tree]);
    }
}
