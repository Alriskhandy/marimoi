<?php

namespace App\Http\Controllers;

use App\Models\DataSpatial;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Pratinjau peta publik di atas skema V2 (spatial_layers/spatial_layer_features),
 * berdampingan dengan FrontendController lama (categories/data_spatial) yang tetap
 * jadi halaman produksi. Lihat docs/marimoi v2/04_implementation/10-plan-peta-skema-baru.md
 * untuk rasional kenapa ini dibuat paralel, bukan swap langsung.
 */
class SpatialMapController extends Controller
{
    public function index(): Response
    {
        return response()->view('frontend.pages.peta-v2');
    }

    public function layerTree(): JsonResponse
    {
        $layers = SpatialLayer::with('children')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->get(['id', 'slug', 'name', 'title', 'color', 'is_marker', 'parent_id']);

        return response()->json($layers);
    }

    public function geojson(SpatialLayer $layer): JsonResponse
    {
        $features = SpatialLayerFeature::where('spatial_layer_id', $layer->id)
            ->selectRaw('id, external_id, region_id, attributes, ST_AsGeoJSON(geometry) as geojson')
            ->get();

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features->map(fn (SpatialLayerFeature $feature) => [
                'type' => 'Feature',
                'geometry' => json_decode($feature->geojson),
                'properties' => [
                    'id' => $feature->id,
                    'external_id' => $feature->external_id,
                    'attributes' => $feature->attributes,
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
            'attributes' => $feature->attributes,
            'region' => $feature->region?->only(['name', 'level']),
            'layer' => $feature->layer->only(['name', 'color']),
            'legacy' => $legacy,
        ]);
    }
}
