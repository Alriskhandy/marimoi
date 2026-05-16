<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AdminFeedbackController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/feedbacks',
        tags: ['Admin Feedbacks'],
        summary: 'List feedback (filterable)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status',          in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'kabupaten_kota',  in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'jenis_tanggapan', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page',        in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50)),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 50), 500);
        $query   = ProjectFeedback::query();

        if ($request->filled('status'))         $query->where('status', $request->status);
        if ($request->filled('kabupaten_kota')) $query->where('kabupaten_kota', $request->kabupaten_kota);
        if ($request->filled('jenis_tanggapan'))$query->where('jenis_tanggapan', $request->jenis_tanggapan);

        $items = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json(['success' => true, 'data' => $items]);
    }

    #[OA\Get(
        path: '/api/v1/admin/feedbacks/{id}',
        tags: ['Admin Feedbacks'],
        summary: 'Detail feedback',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function show(int $id): JsonResponse
    {
        $feedback = ProjectFeedback::with('dataSpatial')->find($id);

        if (!$feedback) {
            return response()->json(['success' => false, 'message' => 'Feedback not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $feedback]);
    }

    #[OA\Post(
        path: '/api/v1/admin/feedbacks',
        tags: ['Admin Feedbacks'],
        summary: 'Buat feedback secara admin',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_pemberi_aspirasi' => 'required|string|max:255',
            'nama_proyek'           => 'required|string|max:255',
            'kabupaten_kota'        => 'required|string|max:255',
            'kecamatan'             => 'nullable|string|max:255',
            'tanggapan'             => 'required|string',
            'jenis_tanggapan'       => 'required|in:keluhan,saran,apresiasi,pertanyaan',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $feedback = ProjectFeedback::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Feedback berhasil ditambahkan',
            'data'    => $feedback,
        ], 201);
    }

    #[OA\Put(
        path: '/api/v1/admin/feedbacks/{id}',
        tags: ['Admin Feedbacks'],
        summary: 'Update feedback',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $feedback = ProjectFeedback::find($id);

        if (!$feedback) {
            return response()->json(['success' => false, 'message' => 'Feedback not found'], 404);
        }

        $feedback->update($request->only([
            'status', 'kabupaten_kota', 'kecamatan', 'tanggapan', 'jenis_tanggapan',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Feedback berhasil diupdate',
            'data'    => $feedback,
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/feedbacks/{id}',
        tags: ['Admin Feedbacks'],
        summary: 'Hapus feedback',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function destroy(int $id): JsonResponse
    {
        $feedback = ProjectFeedback::find($id);

        if (!$feedback) {
            return response()->json(['success' => false, 'message' => 'Feedback not found'], 404);
        }

        $feedback->delete();

        return response()->json(['success' => true, 'message' => 'Feedback berhasil dihapus']);
    }

    #[OA\Put(
        path: '/api/v1/admin/feedbacks/{id}/respond',
        tags: ['Admin Feedbacks'],
        summary: 'Kirim respon admin terhadap feedback',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['respon', 'status'],
                properties: [
                    new OA\Property(property: 'respon', type: 'string'),
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'ditinjau', 'ditindaklanjuti', 'selesai']),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function respond(Request $request, int $id): JsonResponse
    {
        $feedback = ProjectFeedback::find($id);

        if (!$feedback) {
            return response()->json(['success' => false, 'message' => 'Feedback not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'respon' => 'required|string',
            'status' => 'required|in:pending,ditinjau,ditindaklanjuti,selesai',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $feedback->update([
            'respon'       => $request->respon,
            'status'       => $request->status,
            'responded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Respon berhasil dikirim',
            'data'    => $feedback,
        ]);
    }
}
