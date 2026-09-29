<?php

namespace App\Http\Controllers;

use App\Models\Map;
use App\Models\MapLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Susun spatial_layers ke dalam Map (docs/marimoi v2/04_implementation/
 * 11-plan-dashboard-skema-baru.md Bagian B).
 */
class MapLayerController extends Controller
{
    public function store(Request $request, Map $map)
    {
        $validated = $request->validate([
            'spatial_layer_id' => 'required|exists:spatial_layers,id',
        ]);

        if ($map->layers()->where('spatial_layer_id', $validated['spatial_layer_id'])->exists()) {
            return back()->with('error', 'Layer ini sudah ada di peta.');
        }

        $nextOrder = ((int) $map->layers()->max('display_order')) + 1;

        MapLayer::create([
            'map_id' => $map->id,
            'spatial_layer_id' => $validated['spatial_layer_id'],
            'display_order' => $nextOrder,
        ]);

        return back()->with('success', 'Layer berhasil ditambahkan ke peta.');
    }

    public function update(Request $request, Map $map, MapLayer $mapLayer)
    {
        abort_unless($mapLayer->map_id === $map->id, 404);

        $validated = $request->validate([
            'display_name' => 'nullable|string|max:255',
            'opacity' => 'required|numeric|min:0|max:1',
            'is_visible' => 'boolean',
        ]);

        $mapLayer->update($validated);

        return back()->with('success', 'Pengaturan layer berhasil disimpan.');
    }

    public function destroy(Map $map, MapLayer $mapLayer)
    {
        abort_unless($mapLayer->map_id === $map->id, 404);

        $mapLayer->delete();

        return back()->with('success', 'Layer berhasil dihapus dari peta.');
    }

    public function reorder(Request $request, Map $map)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:map_layers,id',
        ]);

        DB::transaction(function () use ($validated, $map) {
            foreach ($validated['order'] as $index => $mapLayerId) {
                MapLayer::where('id', $mapLayerId)->where('map_id', $map->id)->update(['display_order' => $index]);
            }
        });

        return response()->json(['status' => 'ok']);
    }
}
