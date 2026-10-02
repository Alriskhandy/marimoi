<?php

namespace App\Http\Controllers;

use App\Models\SpatialFeedback;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Feedback (docs/marimoi v2/04_implementation/12-implementasi-perbaikan-pemetaan.md
 * Bagian 3.3) — admin index/respond di sini, endpoint publik store() dipanggil dari
 * popup detail /peta-v2 (dokumen 10) tanpa auth.
 */
class SpatialFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $feedbacks = SpatialFeedback::query()
            ->when($request->get('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('backend.pages.spatial-feedbacks.index', compact('feedbacks'));
    }

    public function respond(Request $request, SpatialFeedback $spatialFeedback)
    {
        $validated = $request->validate([
            'response_admin' => 'required|string',
            'status' => ['required', Rule::in(['baru', 'ditinjau', 'ditanggapi'])],
        ]);

        $spatialFeedback->update($validated + ['responded_at' => now()]);

        return redirect()->route('spatial-feedbacks.index')->with('success', 'Feedback berhasil ditanggapi.');
    }

    public function destroy(SpatialFeedback $spatialFeedback)
    {
        $spatialFeedback->delete();

        return redirect()->route('spatial-feedbacks.index')->with('success', 'Feedback berhasil dihapus.');
    }

    /**
     * Endpoint publik — dipanggil dari popup detail Layer/Data Spasial di /peta-v2,
     * tanpa auth (masyarakat umum).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'spatial_layer_id' => 'nullable|exists:spatial_layers_legacy_v2,id',
            'spatial_layer_feature_id' => 'nullable|exists:spatial_layer_features_legacy_v2,id',
            'nama_pemberi' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'pesan' => 'required|string',
        ]);

        $hasLayer = ! empty($validated['spatial_layer_id']);
        $hasFeature = ! empty($validated['spatial_layer_feature_id']);

        if ($hasLayer === $hasFeature) {
            return response()->json([
                'message' => 'Pilih tepat satu target: Layer atau Data Spasial, tidak keduanya/tidak ada.',
            ], 422);
        }

        // Keberadaan target sudah dipastikan oleh rule exists:spatial_layers,id /
        // exists:spatial_layer_features,id di atas (tabel v2) — tidak perlu
        // findOrFail tambahan (dan tidak boleh lewat Eloquent SpatialLayer/
        // SpatialLayerFeature, yang sejak Fase 3 menunjuk tabel v3 berbeda).
        SpatialFeedback::create($validated);

        return response()->json(['message' => 'Feedback berhasil dikirim, terima kasih.'], 201);
    }
}
