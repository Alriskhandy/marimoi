<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AdminPublicationController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/publications',
        tags: ['Admin Publications'],
        summary: 'List publikasi',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 50), 500);
        $items   = Publication::orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json(['success' => true, 'data' => $items]);
    }

    #[OA\Get(
        path: '/api/v1/admin/publications/{publication}',
        tags: ['Admin Publications'],
        summary: 'Detail publikasi',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'publication', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function show(Publication $publication): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $publication]);
    }

    #[OA\Post(
        path: '/api/v1/admin/publications',
        tags: ['Admin Publications'],
        summary: 'Buat publikasi',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title',       type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'category',    type: 'string', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $publication = Publication::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Publikasi berhasil ditambahkan',
            'data'    => $publication,
        ], 201);
    }

    #[OA\Put(
        path: '/api/v1/admin/publications/{publication}',
        tags: ['Admin Publications'],
        summary: 'Update publikasi',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'publication', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function update(Request $request, Publication $publication): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $publication->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Publikasi berhasil diupdate',
            'data'    => $publication,
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/publications/{publication}',
        tags: ['Admin Publications'],
        summary: 'Hapus publikasi',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'publication', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function destroy(Publication $publication): JsonResponse
    {
        $publication->delete();

        return response()->json(['success' => true, 'message' => 'Publikasi berhasil dihapus']);
    }
}
