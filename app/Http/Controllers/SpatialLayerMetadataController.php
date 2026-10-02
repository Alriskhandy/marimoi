<?php

namespace App\Http\Controllers;

use App\Models\LegacyCategory as Category;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerMetadata;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Metadata layer skema v3 (`layer_metadata`, plan mellow-weaving-eclipse
 * Fase 3) — diakses dari daftar categories yang sudah ada; kategori &
 * SpatialLayer tetap dua tabel terpisah (categories masih compatibility
 * source), controller ini murni menjembatani.
 *
 * `layer_metadata` v3 tidak punya kolom `source_name`/`source_url`/
 * `attribution` seperti v2 — "Nama Sumber Data" dipetakan ke
 * `producer_organization` (field terdekat di dokumen §5.5), "URL Sumber"
 * dan "Atribusi" disimpan di `extra` (jsonb) karena tidak ada kolom
 * khususnya. `update_frequency` sekarang dibatasi CHECK constraint ke
 * beberapa nilai tetap, bukan teks bebas.
 */
class SpatialLayerMetadataController extends Controller
{
    private const UPDATE_FREQUENCIES = [
        'once' => 'Sekali saja',
        'annually' => 'Tahunan',
        'semiannually' => 'Semester',
        'quarterly' => 'Triwulan',
        'monthly' => 'Bulanan',
        'irregular' => 'Tidak tentu',
    ];

    public function edit(int $id)
    {
        $category = Category::findOrFail($id);
        $layer = SpatialLayer::where('legacy_category_id', $id)->first();

        if (! $layer) {
            return redirect()->route('categories.index')
                ->with('error', 'Kategori ini belum tersinkron ke Layer (skema baru) — metadata belum bisa diisi.');
        }

        $metadata = $layer->metadata ?? new SpatialLayerMetadata(['layer_id' => $layer->id]);
        $updateFrequencies = self::UPDATE_FREQUENCIES;

        return view('backend.pages.categories.metadata', compact('category', 'layer', 'metadata', 'updateFrequencies'));
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
            'producer_organization' => 'nullable|string|max:255',
            'source_url' => 'nullable|url',
            'license' => 'nullable|string|max:255',
            'attribution' => 'nullable|string',
            'update_frequency' => ['nullable', Rule::in(array_keys(self::UPDATE_FREQUENCIES))],
            'data_year' => 'nullable|integer|min:1900|max:2100',
        ]);

        // Field yang tidak dikirim di request sama sekali TIDAK ikut ditimpa
        // (bukan otomatis jadi null) — hanya key yang benar-benar ada di
        // $validated yang di-set, sisanya tetap seperti nilai lama.
        $metadata = SpatialLayerMetadata::firstOrNew(['layer_id' => $layer->id]);

        foreach (['abstract', 'producer_organization', 'license', 'update_frequency', 'data_year'] as $key) {
            if (array_key_exists($key, $validated)) {
                $metadata->{$key} = $validated[$key];
            }
        }

        if (array_key_exists('source_url', $validated) || array_key_exists('attribution', $validated)) {
            $extra = $metadata->extra ?? [];
            if (array_key_exists('source_url', $validated)) {
                $extra['source_url'] = $validated['source_url'];
            }
            if (array_key_exists('attribution', $validated)) {
                $extra['attribution'] = $validated['attribution'];
            }
            $metadata->extra = $extra;
        }

        $metadata->save();

        return redirect()->route('categories.metadata.edit', $id)->with('success', 'Metadata layer berhasil disimpan.');
    }
}
