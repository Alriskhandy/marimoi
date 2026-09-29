<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerMetadata;
use Illuminate\Http\Request;

/**
 * Metadata layer skema baru (spatial_layer_metadata) — docs/marimoi v2/
 * 04_implementation/11-plan-dashboard-skema-baru.md Bagian A. Diakses dari daftar
 * categories yang sudah ada; kategori & SpatialLayer tetap dua tabel terpisah
 * (categories masih compatibility source), controller ini murni menjembatani.
 */
class SpatialLayerMetadataController extends Controller
{
    public function edit(int $id)
    {
        $category = Category::findOrFail($id);
        $layer = SpatialLayer::where('legacy_category_id', $id)->first();

        if (! $layer) {
            return redirect()->route('categories.index')
                ->with('error', 'Kategori ini belum tersinkron ke Layer (skema baru) — metadata belum bisa diisi.');
        }

        $metadata = $layer->metadata ?? new SpatialLayerMetadata(['spatial_layer_id' => $layer->id]);

        return view('backend.pages.categories.metadata', compact('category', 'layer', 'metadata'));
    }

    public function update(Request $request, int $id)
    {
        $layer = SpatialLayer::where('legacy_category_id', $id)->first();

        if (! $layer) {
            return redirect()->route('categories.index')
                ->with('error', 'Kategori ini belum tersinkron ke Layer (skema baru) — metadata belum bisa diisi.');
        }

        $validated = $request->validate([
            'abstract' => 'nullable|string',
            'source_name' => 'nullable|string|max:255',
            'source_url' => 'nullable|url',
            'license' => 'nullable|string|max:255',
            'attribution' => 'nullable|string',
            'update_frequency' => 'nullable|string|max:50',
            'data_reference_year' => 'nullable|integer|min:1900|max:2100',
        ]);

        SpatialLayerMetadata::updateOrCreate(
            ['spatial_layer_id' => $layer->id],
            $validated + ['updated_by' => auth()->id()]
        );

        return redirect()->route('categories.metadata.edit', $id)->with('success', 'Metadata layer berhasil disimpan.');
    }
}
