<?php

namespace App\Http\Controllers;

use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint geojson layer V2 (spatial_layers/spatial_layer_features), dipakai
 * oleh preview peta di halaman admin spatial-layers/show.blade.php.
 *
 * Disesuaikan ke skema v3 (plan mellow-weaving-eclipse Fase 3) karena model
 * `SpatialLayer`/`SpatialLayerFeature` sudah diarahkan ke layers_v3/
 * spatial_features_v3.
 */
class SpatialMapController extends Controller
{
    public function geojson(SpatialLayer $layer): JsonResponse
    {
        $features = SpatialLayerFeature::where('layer_id', $layer->id)
            ->selectRaw('id, label, region_id, properties, style_override, ST_AsGeoJSON(geom) as geojson')
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
                    // NULL = ikut style default Layer (perilaku lama) — isi
                    // kalau Data Spasial ini dikustom sendiri, lihat
                    // SpatialLayerFeatureController::validated().
                    'style_override' => $feature->style_override,
                ],
            ]),
        ]);
    }
}
