<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\ProjectFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class PublicFeedbackController extends Controller
{
    #[OA\Post(
        path: '/api/v1/feedbacks',
        tags: ['Feedback'],
        summary: 'Submit feedback proyek pembangunan',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['nama_pemberi_aspirasi', 'nama_proyek', 'kabupaten_kota', 'tanggapan', 'jenis_tanggapan'],
                    properties: [
                        new OA\Property(property: 'nama_pemberi_aspirasi', type: 'string'),
                        new OA\Property(property: 'nama_proyek',           type: 'string'),
                        new OA\Property(property: 'kabupaten_kota',        type: 'string'),
                        new OA\Property(property: 'kecamatan',             type: 'string', nullable: true),
                        new OA\Property(property: 'tanggapan',             type: 'string'),
                        new OA\Property(property: 'jenis_tanggapan',       type: 'string', enum: ['keluhan', 'saran', 'apresiasi', 'pertanyaan']),
                        new OA\Property(property: 'email',                 type: 'string', format: 'email', nullable: true),
                        new OA\Property(property: 'phone',                 type: 'string', nullable: true),
                        new OA\Property(property: 'latitude',              type: 'number', format: 'float', nullable: true),
                        new OA\Property(property: 'longitude',             type: 'number', format: 'float', nullable: true),
                        new OA\Property(property: 'laporan_gambar',        type: 'string', format: 'binary', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data_spatial_id'       => 'nullable|exists:data_spatial,id',
            'nama_pemberi_aspirasi' => 'required|string|max:255',
            'nama_proyek'           => 'required|string|max:255',
            'kabupaten_kota'        => 'required|string|max:255',
            'kecamatan'             => 'nullable|string|max:255',
            'tanggapan'             => 'required|string',
            'jenis_tanggapan'       => 'required|in:keluhan,saran,apresiasi,pertanyaan',
            'email'                 => 'nullable|email|max:255',
            'phone'                 => 'nullable|string|max:20',
            'latitude'              => 'nullable|numeric|between:-90,90',
            'longitude'             => 'nullable|numeric|between:-180,180',
            'laporan_gambar'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('laporan_gambar')) {
            $file     = $request->file('laporan_gambar');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/feedback_images', $filename);
            $data['laporan_gambar'] = $filename;
        }

        $feedback = ProjectFeedback::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Feedback berhasil ditambahkan',
            'data'    => $feedback,
        ], 201);
    }
}
