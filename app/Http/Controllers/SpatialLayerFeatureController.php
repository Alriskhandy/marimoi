<?php

namespace App\Http\Controllers;

use App\Models\MapTypeDynamicAttribute;
use App\Models\MetadataDefinition;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use App\Support\SpatialGeometryBatchImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Data Spasial di bawah satu Layer — skema v3 (plan mellow-weaving-eclipse
 * Fase 3). store() mendukung 3 metode input (Shapefile/Koordinat manual/
 * KMZ) lewat SpatialGeometryBatchImporter, bisa membuat BANYAK
 * SpatialLayerFeature sekaligus dalam satu submit.
 *
 * Deviasi dari v2: `attributes` (mentah hasil impor) dan `metadata_dinamis`
 * (terstruktur sesuai skema Jenis) dulu dua kolom jsonb terpisah — di v3
 * tergabung jadi satu `properties` (dokumen tidak membedakan keduanya).
 * Saat update(), nilai metadata_dinamis baru di-merge ke atas `properties`
 * yang sudah ada (bukan overwrite total) supaya atribut mentah hasil impor
 * tidak hilang.
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

    public function store(Request $request, SpatialLayer $spatialLayer, SpatialGeometryBatchImporter $importer)
    {
        $request->validate([
            'input_type' => ['required', Rule::in(['shapefile', 'coordinates', 'kmz'])],
        ]);

        $metadataDinamis = $this->validatedMetadataDinamis($request, $spatialLayer);
        $gambarPath = $this->storeGambarIfPresent($request);

        try {
            $results = match ($request->input('input_type')) {
                'shapefile' => $this->importFromShapefile($request, $importer),
                'coordinates' => $this->importFromCoordinates($request, $importer),
                'kmz' => $this->importFromKmz($request, $importer),
            };
        } catch (\Exception $e) {
            if ($gambarPath) {
                Storage::disk('public')->delete($gambarPath);
            }

            return redirect()->back()->withErrors(['input_type' => $e->getMessage()])->withInput();
        }

        DB::transaction(function () use ($results, $spatialLayer, $metadataDinamis, $gambarPath) {
            foreach ($results as $result) {
                $quotedWkt = DB::connection()->getPdo()->quote($result['wkt']);

                SpatialLayerFeature::create([
                    'layer_id' => $spatialLayer->id,
                    'geom' => DB::raw("ST_GeomFromText({$quotedWkt}, 4326)"),
                    'properties' => array_merge($result['attributes'], $metadataDinamis),
                    'gambar' => $gambarPath,
                    'created_by' => auth()->id(),
                ]);
            }
        });

        $count = count($results);

        return redirect()->route('spatial-layers.show', $spatialLayer)
            ->with('success', "Berhasil menyimpan {$count} Data Spasial.");
    }

    /**
     * @return array<int, array{wkt: string, attributes: array}>
     */
    private function importFromShapefile(Request $request, SpatialGeometryBatchImporter $importer): array
    {
        $request->validate([
            'shp_file' => 'required|file',
            'shx_file' => 'required|file',
            'dbf_file' => 'required|file',
        ]);

        return $importer->fromShapefile($request->file('shp_file'), $request->file('shx_file'), $request->file('dbf_file'));
    }

    /**
     * @return array<int, array{wkt: string, attributes: array}>
     */
    private function importFromCoordinates(Request $request, SpatialGeometryBatchImporter $importer): array
    {
        $request->validate([
            'coordinates' => 'required|array|min:1',
            'coordinates.*.latitude' => 'nullable|numeric|between:-90,90',
            'coordinates.*.longitude' => 'nullable|numeric|between:-180,180',
            'coordinates.*.name' => 'nullable|string|max:255',
        ]);

        return $importer->fromCoordinates($request->input('coordinates'));
    }

    /**
     * @return array<int, array{wkt: string, attributes: array}>
     */
    private function importFromKmz(Request $request, SpatialGeometryBatchImporter $importer): array
    {
        $request->validate([
            'kmz_file' => 'required|file',
        ]);

        return $importer->fromKmz($request->file('kmz_file'));
    }

    public function edit(SpatialLayer $spatialLayer, SpatialLayerFeature $feature)
    {
        abort_unless($feature->layer_id === $spatialLayer->id, 404);

        $dynamicAttributes = $this->activeDynamicAttributesFor($spatialLayer);
        $geometryWkt = DB::table('spatial_features')
            ->where('id', $feature->id)
            ->selectRaw('ST_AsText(geom) as wkt')
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
        abort_unless($feature->layer_id === $spatialLayer->id, 404);

        $validated = $this->validated($request, $spatialLayer, $feature);

        $feature->update($validated);

        return redirect()->route('spatial-layers.show', $spatialLayer)->with('success', 'Data Spasial berhasil diperbarui.');
    }

    public function destroy(SpatialLayer $spatialLayer, SpatialLayerFeature $feature)
    {
        abort_unless($feature->layer_id === $spatialLayer->id, 404);

        $feature->delete();

        return redirect()->route('spatial-layers.show', $spatialLayer)->with('success', 'Data Spasial berhasil dihapus.');
    }

    private function activeDynamicAttributesFor(SpatialLayer $layer)
    {
        if (! $layer->map_type_id) {
            return collect();
        }

        return MapTypeDynamicAttribute::where('map_type_id', $layer->map_type_id)
            ->where('is_active', true)
            ->with('metadataDefinition')
            ->orderBy('urutan')
            ->get();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function metadataDinamisRules(SpatialLayer $layer): array
    {
        $rules = [];

        foreach ($this->activeDynamicAttributesFor($layer) as $attribute) {
            $definition = $attribute->metadataDefinition;
            $fieldRules = [$attribute->is_wajib ? 'required' : 'nullable'];

            if ($definition->data_type === MetadataDefinition::TYPE_SELECT && ! empty($definition->opsi)) {
                $fieldRules[] = Rule::in($definition->opsi);
            } else {
                $fieldRules[] = 'string';
            }

            $rules['metadata_dinamis.'.$definition->kode] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedMetadataDinamis(Request $request, SpatialLayer $layer): array
    {
        $validated = $request->validate($this->metadataDinamisRules($layer));

        return $validated['metadata_dinamis'] ?? [];
    }

    /**
     * Simpan file gambar yang di-upload (kalau ada) — dipakai untuk SEMUA Data
     * Spasial yang dibuat dalam satu submit store() (batch Shapefile/KMZ/Koordinat).
     */
    private function storeGambarIfPresent(Request $request): ?string
    {
        $request->validate(['gambar' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048']);

        if (! $request->hasFile('gambar')) {
            return null;
        }

        $file = $request->file('gambar');
        $fileName = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();

        return $file->storeAs('images/spatial-layer-features', $fileName, 'public');
    }

    private function validated(Request $request, SpatialLayer $layer, ?SpatialLayerFeature $feature = null): array
    {
        $rules = ['geometry_wkt' => 'required|string', 'gambar' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048']
            + $this->metadataDinamisRules($layer);

        $validated = $request->validate($rules);

        // Escape lewat PDO::quote() (bukan addslashes) supaya aman dari SQL injection
        // untuk driver Postgres, sebelum dibungkus DB::raw() — WKT tidak bisa dikirim
        // sebagai binding biasa karena perlu masuk ke dalam pemanggilan ST_GeomFromText().
        $quotedWkt = DB::connection()->getPdo()->quote($validated['geometry_wkt']);

        $result = [
            'geom' => DB::raw("ST_GeomFromText({$quotedWkt}, 4326)"),
            'properties' => array_merge($feature?->properties ?? [], $validated['metadata_dinamis'] ?? []),
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
