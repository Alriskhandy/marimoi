<?php

namespace App\Http\Controllers;

use App\Models\MapTypeDynamicAttribute;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data Spasial di bawah satu Layer (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 3.2) — form punya dua bagian atribut
 * terpisah (Keputusan #2): attributes (mentah, hasil impor — read-only di sini,
 * pengisian shapefile/KMZ/KML tetap lewat DataSpatialController yang sudah ada,
 * BUKAN dipindah ke sini) dan metadata_dinamis (terstruktur sesuai skema Jenis).
 */
class SpatialLayerFeatureController extends Controller
{
    public function create(SpatialLayer $spatialLayer)
    {
        $dynamicAttributes = $this->activeDynamicAttributesFor($spatialLayer);

        return view('backend.pages.spatial-layers.features.create', [
            'layer' => $spatialLayer,
            'dynamicAttributes' => $dynamicAttributes,
        ]);
    }

    public function store(Request $request, SpatialLayer $spatialLayer)
    {
        $validated = $this->validated($request, $spatialLayer);

        SpatialLayerFeature::create($validated + [
            'spatial_layer_id' => $spatialLayer->id,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('spatial-layers.edit', $spatialLayer)->with('success', 'Data Spasial berhasil ditambahkan.');
    }

    public function edit(SpatialLayer $spatialLayer, SpatialLayerFeature $feature)
    {
        abort_unless($feature->spatial_layer_id === $spatialLayer->id, 404);

        $dynamicAttributes = $this->activeDynamicAttributesFor($spatialLayer);
        $geometryWkt = DB::table('spatial_layer_features')
            ->where('id', $feature->id)
            ->selectRaw('ST_AsText(geometry) as wkt')
            ->value('wkt');

        return view('backend.pages.spatial-layers.features.edit', [
            'layer' => $spatialLayer,
            'feature' => $feature,
            'dynamicAttributes' => $dynamicAttributes,
            'geometryWkt' => $geometryWkt,
        ]);
    }

    public function update(Request $request, SpatialLayer $spatialLayer, SpatialLayerFeature $feature)
    {
        abort_unless($feature->spatial_layer_id === $spatialLayer->id, 404);

        $validated = $this->validated($request, $spatialLayer, $feature);

        $feature->update($validated);

        return redirect()->route('spatial-layers.edit', $spatialLayer)->with('success', 'Data Spasial berhasil diperbarui.');
    }

    public function destroy(SpatialLayer $spatialLayer, SpatialLayerFeature $feature)
    {
        abort_unless($feature->spatial_layer_id === $spatialLayer->id, 404);

        $feature->delete();

        return redirect()->route('spatial-layers.edit', $spatialLayer)->with('success', 'Data Spasial berhasil dihapus.');
    }

    private function activeDynamicAttributesFor(SpatialLayer $layer)
    {
        if (! $layer->map_type_id) {
            return collect();
        }

        return MapTypeDynamicAttribute::where('map_type_id', $layer->map_type_id)
            ->where('is_active', true)
            ->orderBy('urutan')
            ->get();
    }

    private function validated(Request $request, SpatialLayer $layer, ?SpatialLayerFeature $feature = null): array
    {
        $rules = [
            'geometry_wkt' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
        ];

        $dynamicAttributes = $this->activeDynamicAttributesFor($layer);
        foreach ($dynamicAttributes as $attribute) {
            $rules['metadata_dinamis.'.$attribute->kode_atribut] = $attribute->is_wajib ? 'required|string' : 'nullable|string';
        }

        $validated = $request->validate($rules);

        // Escape lewat PDO::quote() (bukan addslashes) supaya aman dari SQL injection
        // untuk driver Postgres, sebelum dibungkus DB::raw() — WKT tidak bisa dikirim
        // sebagai binding biasa karena perlu masuk ke dalam pemanggilan ST_GeomFromText().
        $quotedWkt = DB::connection()->getPdo()->quote($validated['geometry_wkt']);

        $result = [
            'geometry' => DB::raw("ST_GeomFromText({$quotedWkt}, 4326)"),
            'metadata_dinamis' => $validated['metadata_dinamis'] ?? [],
        ];

        if ($request->hasFile('gambar')) {
            if ($feature?->gambar && Storage::disk('public')->exists($feature->gambar)) {
                Storage::disk('public')->delete($feature->gambar);
            }

            $file = $request->file('gambar');
            $fileName = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
            $result['gambar'] = $file->storeAs('images/spatial-layer-features', $fileName, 'public');
        }

        return $result;
    }
}
