<?php

namespace App\Http\Controllers;

use App\Models\SpatialFeedback;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Feedback (docs/marimoi v2/04_implementation/12-implementasi-perbaikan-pemetaan.md
 * Bagian 3.3) — admin index/respond/destroy untuk feedback yang sudah masuk.
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
}
