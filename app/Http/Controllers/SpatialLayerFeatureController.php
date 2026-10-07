<?php

namespace App\Http\Controllers;

use App\Models\AdministrativeRegion;
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
 * Data Spasial di bawah satu Layer — skema v3. `store()` di sini HANYA untuk
 * input koordinat manual (satu langkah, tanpa pemetaan kolom — hasil parsing
 * koordinat sudah punya nama kolom tetap: NAMA/LATITUDE/LONGITUDE/INPUT_TYPE,
 * tidak ada yang perlu dipetakan).
 *
 * Impor file (Shapefile/KMZ) sejak Fase D.2 (plan mellow-weaving-eclipse,
 * implementasi spec-admin-manajemen-peta.md §5.5 butir 3) PINDAH ke wizard
 * 2 tahap di `LayerImportController` (upload → deteksi kolom → pemetaan →
 * proses), karena kolom hasil impor file sifatnya bebas/tidak terduga dan
 * perlu dipetakan admin ke atribut standar sebelum disimpan sebagai
 * `properties`.
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
        $this->authorizeOpdAccess($spatialLayer);

        $dynamicAttributes = $this->activeDynamicAttributesFor($spatialLayer);

        return view('backend.pages.spatial-layers.features.create', [
            'layer' => $spatialLayer,
            'dynamicAttributes' => $dynamicAttributes,
            'regionsByLevel' => AdministrativeRegion::optionsGroupedByLevel(),
        ]);
    }

    public function store(Request $request, SpatialLayer $spatialLayer, SpatialGeometryBatchImporter $importer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        $validated = $request->validate([
            'input_type' => ['required', Rule::in(['coordinates'])],
            'region_id' => 'nullable|exists:administrative_regions,id',
        ]);

        $metadataDinamis = $this->validatedMetadataDinamis($request, $spatialLayer);
        $gambarPath = $this->storeGambarIfPresent($request);
        $regionId = $validated['region_id'] ?? null;

        try {
            $results = $this->importFromCoordinates($request, $importer);
        } catch (\Exception $e) {
            if ($gambarPath) {
                Storage::disk('public')->delete($gambarPath);
            }

            return redirect()->back()->withErrors(['input_type' => $e->getMessage()])->withInput();
        }

        DB::transaction(function () use ($results, $spatialLayer, $metadataDinamis, $gambarPath, $regionId) {
            foreach ($results as $result) {
                $quotedWkt = DB::connection()->getPdo()->quote($result['wkt']);

                SpatialLayerFeature::create([
                    'layer_id' => $spatialLayer->id,
                    'region_id' => $regionId,
                    'geom' => DB::raw("ST_GeomFromText({$quotedWkt}, 4326)"),
                    'properties' => array_merge($result['attributes'], $metadataDinamis),
                    'gambar' => $gambarPath,
                    'created_by' => auth()->id(),
                ]);
            }
        });

        $spatialLayer->refreshFeatureCache();

        $count = count($results);

        return redirect()->route('spatial-layers.show', $spatialLayer)
            ->with('success', "Berhasil menyimpan {$count} Data Spasial.");
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

    public function edit(SpatialLayer $spatialLayer, SpatialLayerFeature $feature)
    {
        abort_unless($feature->layer_id === $spatialLayer->id, 404);
        $this->authorizeOpdAccess($spatialLayer);

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
            'regionsByLevel' => AdministrativeRegion::optionsGroupedByLevel(),
        ]);
    }

    public function update(Request $request, SpatialLayer $spatialLayer, SpatialLayerFeature $feature)
    {
        abort_unless($feature->layer_id === $spatialLayer->id, 404);
        $this->authorizeOpdAccess($spatialLayer);

        $validated = $this->validated($request, $spatialLayer, $feature);

        $feature->update($validated);
        $spatialLayer->refreshFeatureCache();

        return redirect()->route('spatial-layers.show', $spatialLayer)->with('success', 'Data Spasial berhasil diperbarui.');
    }

    /**
     * Style satu Layer (layer_styles.definition) berlaku untuk SEMUA Data
     * Spasial miliknya secara default — action ini satu-satunya tempat yang
     * boleh mengubah `style_override` (warna/ukuran/opacity/marker SATU Data
     * Spasial berbeda dari Layer-nya), disatukan di halaman "Style Layer"
     * (LayerStyleController::index()) bersama style default, bukan tersebar
     * ke halaman "Kelola Data Spasial" seperti sebelumnya — supaya admin
     * tidak bolak-balik dua tempat untuk satu urusan (style).
     */
    /**
     * Dipanggil dari dua tempat: form biasa di halaman "Style Layer"
     * (LayerStyleController::index()) DAN fetch() AJAX dari popup edit
     * langsung di peta (spatial-layers/show.blade.php, `onEachFeature`) —
     * yang kedua mengirim `Accept: application/json` supaya redirect+flash
     * normal diganti respons JSON (dan ValidationException otomatis jadi 422
     * JSON juga, bukan redirect, lewat exception handler bawaan Laravel).
     */
    public function updateStyle(Request $request, SpatialLayer $spatialLayer, SpatialLayerFeature $feature)
    {
        abort_unless($feature->layer_id === $spatialLayer->id, 404);
        $this->authorizeOpdAccess($spatialLayer);

        $validated = $request->validate([
            'custom_style' => 'nullable|boolean',
            'style_color' => 'required_if:custom_style,1|nullable|string|max:20',
            'style_size' => 'required_if:custom_style,1|nullable|numeric|min:1|max:100',
            'style_opacity' => 'required_if:custom_style,1|nullable|numeric|min:0|max:1',
            'style_is_marker' => 'nullable|boolean',
            'style_icon' => 'nullable|string|max:100',
        ]);

        $styleOverride = ($validated['custom_style'] ?? false) ? [
            'color' => $validated['style_color'],
            'size' => (float) $validated['style_size'],
            'opacity' => (float) $validated['style_opacity'],
            'is_marker' => (bool) ($validated['style_is_marker'] ?? false),
            'icon' => $validated['style_icon'] ?? null,
        ] : null;

        $feature->update(['style_override' => $styleOverride]);

        if ($request->wantsJson()) {
            return response()->json(['style_override' => $styleOverride]);
        }

        return redirect()->route('spatial-layers.styles.index', $spatialLayer)
            ->with('success', 'Style Data Spasial berhasil diperbarui.');
    }

    public function destroy(SpatialLayer $spatialLayer, SpatialLayerFeature $feature)
    {
        abort_unless($feature->layer_id === $spatialLayer->id, 404);
        $this->authorizeOpdAccess($spatialLayer);

        $feature->delete();
        $spatialLayer->refreshFeatureCache();

        return redirect()->route('spatial-layers.show', $spatialLayer)->with('success', 'Data Spasial berhasil dihapus.');
    }

    /**
     * Bulk edit satu atribut (§5.7 butir 3) — porting dari
     * `DataSpatialController::bulkUpdateAttribute()` (modul lama), bentuknya
     * sengaja dipertahankan sama: satu kunci atribut + satu aksi (set/remove),
     * bukan merge properties penuh, supaya admin tidak bisa tidak sengaja
     * menimpa atribut lain. Per-baris save() (bukan mass update query) supaya
     * tidak melewati cast `properties` (array -> jsonb).
     */
    public function bulkUpdateAttribute(Request $request, SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => ['required', 'integer'],
            'action' => ['required', Rule::in(['set', 'remove'])],
            'key' => ['required', 'string', 'regex:/^[A-Za-z_][A-Za-z0-9_]*$/'],
            'value' => ['required_if:action,set', 'nullable', 'string', 'max:1000'],
        ], [
            'ids.required' => 'Tidak ada Data Spasial yang dipilih.',
            'key.regex' => 'Nama atribut hanya boleh huruf, angka, dan underscore, diawali huruf/underscore.',
        ]);

        $features = SpatialLayerFeature::where('layer_id', $spatialLayer->id)
            ->whereIn('id', $validated['ids'])
            ->get();

        DB::transaction(function () use ($features, $validated) {
            foreach ($features as $feature) {
                $properties = $feature->properties ?? [];

                if ($validated['action'] === 'set') {
                    $properties[$validated['key']] = $validated['value'];
                } else {
                    unset($properties[$validated['key']]);
                }

                $feature->properties = $properties;
                $feature->save();
            }
        });

        return redirect()->route('spatial-layers.show', $spatialLayer)
            ->with('success', "Berhasil mengubah atribut {$features->count()} Data Spasial.");
    }

    public function bulkDestroy(Request $request, SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => ['required', 'integer'],
        ], [
            'ids.required' => 'Tidak ada Data Spasial yang dipilih.',
        ]);

        $count = SpatialLayerFeature::where('layer_id', $spatialLayer->id)
            ->whereIn('id', $validated['ids'])
            ->delete();

        $spatialLayer->refreshFeatureCache();

        return redirect()->route('spatial-layers.show', $spatialLayer)
            ->with('success', "Berhasil menghapus {$count} Data Spasial.");
    }

    /**
     * Pindahkan Data Spasial TERPILIH (checkbox tabel di spatial-layers/show.blade.php)
     * ke Layer lain — berguna kalau sebagian data ternyata salah Layer atau
     * mau dipecah ke Layer baru. `style_override` per Data Spasial (kalau
     * ada) ikut terbawa apa adanya, tidak direset — warnanya masih
     * konsisten bagi admin yang sudah mengaturnya secara khusus.
     */
    public function bulkMoveToLayer(Request $request, SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => ['required', 'integer'],
            'target_layer_id' => ['required', 'uuid', Rule::notIn([$spatialLayer->id]), 'exists:layers,id'],
        ], [
            'ids.required' => 'Tidak ada Data Spasial yang dipilih.',
            'target_layer_id.not_in' => 'Pilih Layer tujuan yang berbeda dari Layer ini.',
        ]);

        $targetLayer = SpatialLayer::findOrFail($validated['target_layer_id']);
        $this->authorizeOpdAccess($targetLayer);

        $count = SpatialLayerFeature::where('layer_id', $spatialLayer->id)
            ->whereIn('id', $validated['ids'])
            ->update(['layer_id' => $targetLayer->id]);

        $spatialLayer->refreshFeatureCache();
        $targetLayer->refreshFeatureCache();

        return redirect()->route('spatial-layers.show', $spatialLayer)
            ->with('success', "{$count} Data Spasial berhasil dipindahkan ke Layer \"{$targetLayer->name}\".");
    }

    /**
     * Pindahkan SEMUA Data Spasial satu Layer ke Layer lain sekaligus —
     * dipicu dari Daftar Layer (index.blade.php), berguna untuk
     * menggabungkan dua Layer yang ternyata sama/duplikat. Layer asal jadi
     * kosong (feature_count 0) setelah ini, bukan ikut terhapus — admin
     * tetap bisa menghapusnya sendiri lewat aksi Hapus kalau memang sudah
     * tidak dipakai.
     */
    public function moveAllFeatures(Request $request, SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        $validated = $request->validate([
            'target_layer_id' => ['required', 'uuid', Rule::notIn([$spatialLayer->id]), 'exists:layers,id'],
        ], [
            'target_layer_id.not_in' => 'Pilih Layer tujuan yang berbeda dari Layer ini.',
        ]);

        $targetLayer = SpatialLayer::findOrFail($validated['target_layer_id']);
        $this->authorizeOpdAccess($targetLayer);

        $count = SpatialLayerFeature::where('layer_id', $spatialLayer->id)->update(['layer_id' => $targetLayer->id]);

        $spatialLayer->refreshFeatureCache();
        $targetLayer->refreshFeatureCache();

        return redirect()->route('spatial-layers.index')
            ->with($count > 0 ? 'success' : 'error', $count > 0
                ? "{$count} Data Spasial dari \"{$spatialLayer->name}\" berhasil dipindahkan ke \"{$targetLayer->name}\"."
                : "Layer \"{$spatialLayer->name}\" tidak memiliki Data Spasial untuk dipindahkan.");
    }

    private function authorizeOpdAccess(SpatialLayer $layer): void
    {
        $user = auth()->user();

        if ($user?->role?->slug === 'admin-opd' && $layer->opd_id !== $user->opd_id) {
            abort(403, 'Anda tidak memiliki akses ke Layer milik OPD lain.');
        }
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
        $rules = [
            'geometry_wkt' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'region_id' => 'nullable|exists:administrative_regions,id',
        ] + $this->metadataDinamisRules($layer);

        $validated = $request->validate($rules);

        // Escape lewat PDO::quote() (bukan addslashes) supaya aman dari SQL injection
        // untuk driver Postgres, sebelum dibungkus DB::raw() — WKT tidak bisa dikirim
        // sebagai binding biasa karena perlu masuk ke dalam pemanggilan ST_GeomFromText().
        $quotedWkt = DB::connection()->getPdo()->quote($validated['geometry_wkt']);

        $result = [
            'geom' => DB::raw("ST_GeomFromText({$quotedWkt}, 4326)"),
            'region_id' => $validated['region_id'] ?? null,
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
