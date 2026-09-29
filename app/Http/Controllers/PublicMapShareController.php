<?php

namespace App\Http\Controllers;

use App\Models\MapShare;
use App\Models\SpatialLayer;
use Illuminate\Http\Request;

/**
 * Halaman publik untuk membuka link berbagi (docs/marimoi v2/04_implementation/
 * 11-plan-dashboard-skema-baru.md Bagian D.2) — wajib ada supaya MapShare::generateFor()
 * benar-benar bisa dipakai, bukan cuma token yang tidak kemana-mana.
 */
class PublicMapShareController extends Controller
{
    public function show(Request $request, string $token)
    {
        $share = MapShare::findByToken($token);

        abort_if(! $share || ! $share->isValid(), 404);

        $share->recordAccess(
            ipHash: hash('sha256', (string) $request->ip()),
            userAgent: $request->userAgent(),
            referer: $request->header('referer'),
        );

        $snapshot = $share->publication->config_snapshot;
        $layerIds = collect($snapshot['layers'] ?? [])->pluck('spatial_layer_id');
        $slugsById = SpatialLayer::whereIn('id', $layerIds)->pluck('slug', 'id');

        $layers = collect($snapshot['layers'] ?? [])
            ->map(function (array $layer) use ($slugsById) {
                $layer['slug'] = $slugsById[$layer['spatial_layer_id']] ?? null;

                return $layer;
            })
            ->filter(fn (array $layer) => $layer['slug'] !== null)
            ->values();

        return view('frontend.pages.map-share', [
            'title' => $snapshot['title'] ?? 'Peta',
            'description' => $snapshot['description'] ?? null,
            'layers' => $layers,
        ]);
    }
}
