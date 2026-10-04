<?php

namespace App\Http\Controllers;

use App\Models\LayerSource;
use App\Models\SpatialLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

/**
 * Sumber data layanan eksternal per Layer (spec-admin-manajemen-peta.md §5.4,
 * Fase E) — layer bertipe `service_*`/`raster_cog` (lihat `LayerType::
 * stores_features`) dirender langsung dari sini di peta publik, tanpa baris
 * `spatial_features`.
 *
 * `database` (D20) & `file_upload` SENGAJA tidak ditawarkan di form — nilainya
 * tetap ada di CHECK constraint skema, tapi belum ada kebutuhan UI untuk
 * "layer dirender dari database eksternal", dan file_upload direkam lewat
 * `layer_imports` (LayerImportController), bukan `layer_sources`.
 */
class LayerSourceController extends Controller
{
    private const SOURCE_TYPES = [
        'wms' => 'WMS',
        'wmts' => 'WMTS',
        'wfs' => 'WFS',
        'xyz' => 'XYZ Tiles',
        'arcgis_rest' => 'ArcGIS REST',
        'geojson_url' => 'GeoJSON URL',
        'vector_tile' => 'Vector Tile',
        'cog_url' => 'Cloud-Optimized GeoTIFF (COG) URL',
    ];

    public function index(SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        return view('backend.pages.spatial-layers.sources.index', [
            'layer' => $spatialLayer,
            'sources' => $spatialLayer->sources()->orderByDesc('is_primary')->orderBy('name')->get(),
            'sourceTypes' => self::SOURCE_TYPES,
        ]);
    }

    public function store(Request $request, SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        $validated = $this->validated($request);

        DB::transaction(function () use ($spatialLayer, $validated) {
            if ($validated['is_primary'] ?? false) {
                $spatialLayer->sources()->where('is_primary', true)->update(['is_primary' => false]);
            }

            $spatialLayer->sources()->create($validated);
        });

        return redirect()->route('spatial-layers.sources.index', $spatialLayer)->with('success', 'Sumber data berhasil ditambahkan.');
    }

    public function update(Request $request, SpatialLayer $spatialLayer, LayerSource $source)
    {
        $this->authorizeOpdAccess($spatialLayer);
        abort_unless($source->layer_id === $spatialLayer->id, 404);

        $validated = $this->validated($request);

        DB::transaction(function () use ($spatialLayer, $source, $validated) {
            if (($validated['is_primary'] ?? false) && ! $source->is_primary) {
                $spatialLayer->sources()->where('is_primary', true)->update(['is_primary' => false]);
            }

            $source->update($validated);
        });

        return redirect()->route('spatial-layers.sources.index', $spatialLayer)->with('success', 'Sumber data berhasil diperbarui.');
    }

    public function destroy(SpatialLayer $spatialLayer, LayerSource $source)
    {
        $this->authorizeOpdAccess($spatialLayer);
        abort_unless($source->layer_id === $spatialLayer->id, 404);

        $source->delete();

        return redirect()->route('spatial-layers.sources.index', $spatialLayer)->with('success', 'Sumber data berhasil dihapus.');
    }

    /**
     * Uji koneksi sederhana (R: §5.4 butir 3) — bukan validasi penuh
     * kapabilitas WMS/WFS, cuma memastikan URL-nya benar-benar merespons.
     */
    public function testConnection(SpatialLayer $spatialLayer, LayerSource $source)
    {
        $this->authorizeOpdAccess($spatialLayer);
        abort_unless($source->layer_id === $spatialLayer->id, 404);

        try {
            $healthy = Http::timeout(5)->get($source->url)->successful();
        } catch (\Throwable) {
            $healthy = false;
        }

        $source->update([
            'health_status' => $healthy ? 'ok' : 'error',
            'last_checked_at' => now(),
        ]);

        return redirect()->route('spatial-layers.sources.index', $spatialLayer)
            ->with($healthy ? 'success' : 'error', $healthy ? 'Koneksi berhasil.' : 'Koneksi gagal atau tidak merespons.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'source_type' => ['required', Rule::in(array_keys(self::SOURCE_TYPES))],
            'name' => 'nullable|string|max:150',
            'url' => 'required|url|max:2000',
            'service_layer_name' => 'nullable|string|max:200',
            'format' => 'nullable|string|max:50',
            'crs' => 'nullable|string|max:30',
            'auth_type' => ['nullable', Rule::in(['none', 'api_key', 'basic', 'token'])],
            // R11: kredensial MENTAH tidak boleh masuk database — field ini
            // hanya referensi (nama entri di secret store/env), ditegakkan
            // lewat peringatan UI (lihat view), bukan validasi format di sini.
            'credential_ref' => 'nullable|string|max:200',
            'is_primary' => 'boolean',
        ]);
    }

    private function authorizeOpdAccess(SpatialLayer $layer): void
    {
        $user = Auth::user();

        if ($user?->role?->slug === 'admin-opd' && $layer->opd_id !== $user->opd_id) {
            abort(403, 'Anda tidak memiliki akses ke Layer milik OPD lain.');
        }
    }
}
