<?php

namespace App\Http\Controllers;

use App\Models\DataSpatial;
use App\Models\MapTypeDynamicAttribute;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Pratinjau peta publik di atas skema V2 (spatial_layers/spatial_layer_features),
 * berdampingan dengan FrontendController lama (categories/data_spatial) yang tetap
 * jadi halaman produksi. Lihat docs/marimoi v2/04_implementation/10-plan-peta-skema-baru.md
 * untuk rasional kenapa ini dibuat paralel, bukan swap langsung.
 *
 * Disesuaikan ke skema v3 (plan mellow-weaving-eclipse Fase 3) karena model
 * `SpatialLayer`/`SpatialLayerFeature` sudah diarahkan ke layers_v3/
 * spatial_features_v3 — penyesuaian MINIMAL supaya endpoint ini tidak error
 * (bukan redesain penuh; perbandingan output lama vs baru sebelum cutover
 * publik sungguhan tetap Fase 4 terpisah, lihat plan).
 */
class SpatialMapController extends Controller
{
    public function index(): Response
    {
        return response()->view('frontend.pages.peta-v2');
    }

    /**
     * Layer tidak lagi bertingkat antar-sesama di v3 (parent_id dihapus,
     * organisasi pindah ke categories_v3/category_nodes) — daftar flat
     * layer published, bukan tree.
     */
    public function layerTree(): JsonResponse
    {
        $layers = SpatialLayer::with('defaultStyle')
            ->where('status', 'published')
            ->get(['id', 'slug', 'name', 'short_description', 'default_opacity', 'default_style_id', 'category_id', 'category_node_id']);

        return response()->json($layers->map(fn (SpatialLayer $layer) => [
            'id' => $layer->id,
            'slug' => $layer->slug,
            'name' => $layer->name,
            'title' => $layer->short_description,
            'color' => $layer->color,
            'icon' => $layer->icon,
            'opacity' => $layer->opacity,
            'is_marker' => $layer->is_marker,
        ]));
    }

    public function geojson(SpatialLayer $layer): JsonResponse
    {
        $features = SpatialLayerFeature::where('layer_id', $layer->id)
            ->selectRaw('id, label, region_id, properties, ST_AsGeoJSON(geom) as geojson')
            ->get();

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features->map(fn (SpatialLayerFeature $feature) => [
                'type' => 'Feature',
                'geometry' => json_decode($feature->geojson),
                'properties' => [
                    'id' => $feature->id,
                    'external_id' => $feature->label,
                    'attributes' => $feature->properties,
                    'region_id' => $feature->region_id,
                ],
            ]),
        ]);
    }

    public function featureDetail(SpatialLayerFeature $feature): JsonResponse
    {
        $legacy = $feature->legacy_data_spatial_id
            ? DataSpatial::select('deskripsi', 'sumber_data', 'tahun', 'gambar')
                ->find($feature->legacy_data_spatial_id)
            : null;

        return response()->json([
            'attributes' => $feature->properties,
            'metadata_dinamis' => $this->labeledMetadataDinamis($feature),
            'gambar' => $feature->gambar,
            'region' => $feature->region?->only(['name', 'level']),
            'layer' => $feature->layer->only(['name', 'color']),
            'legacy' => $legacy,
        ]);
    }

    /**
     * Nilai atribut dinamis (Bagian 1.4/5 docs/marimoi v2/04_implementation/
     * 12-implementasi-perbaikan-pemetaan.md) sekarang tergabung di `properties`
     * (bersama atribut mentah hasil impor, lihat Fase 2 migrasi) — disaring di
     * sini berdasarkan kode yang memang terdaftar sebagai atribut dinamis
     * Jenis Peta layer-nya, supaya popup detail tidak menampilkan field mentah
     * yang tidak relevan.
     *
     * @return array<int, array{label: string, satuan: ?string, value: mixed}>
     */
    private function labeledMetadataDinamis(SpatialLayerFeature $feature): array
    {
        $values = $feature->properties ?? [];

        if (empty($values) || ! $feature->layer->map_type_id) {
            return [];
        }

        $definitions = MapTypeDynamicAttribute::where('map_type_id', $feature->layer->map_type_id)
            ->with('metadataDefinition')
            ->get()
            ->pluck('metadataDefinition')
            ->filter(fn ($definition) => in_array($definition->kode, array_keys($values), true))
            ->keyBy('kode');

        $result = [];
        foreach ($definitions as $kode => $definition) {
            $result[] = [
                'label' => $definition->label,
                'satuan' => $definition->satuan,
                'value' => $values[$kode],
            ];
        }

        return $result;
    }
}
