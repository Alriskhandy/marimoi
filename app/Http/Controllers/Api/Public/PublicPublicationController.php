<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PublicPublicationController extends Controller
{
    #[OA\Get(
        path: '/api/v1/publications',
        tags: ['Publications'],
        summary: 'List publikasi (bebas akses)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'category', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search',   in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Publication::query()->select([
            'id', 'title', 'description', 'category',
            'cover', 'download_count', 'file_type', 'file_size', 'file_path', 'created_at',
        ]);

        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }

        if ($request->filled('search')) {
            $query->where('title', 'ILIKE', '%' . $request->get('search') . '%');
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $items = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $items->through(fn ($pub) => array_merge($pub->toArray(), [
            'download_url' => $pub->file_url,
        ]));

        return response()->json(['success' => true, 'data' => $items]);
    }

    #[OA\Get(
        path: '/api/v1/publications/{id}',
        tags: ['Publications'],
        summary: 'Detail publikasi',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $publication = Publication::select([
            'id', 'title', 'description', 'category',
            'cover', 'download_count', 'file_type', 'file_size',
            'file_name', 'file_path', 'created_at',
        ])->find($id);

        if (!$publication) {
            return response()->json(['success' => false, 'message' => 'Publikasi tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => array_merge($publication->toArray(), [
            'download_url' => $publication->file_url,
        ])]);
    }
}
