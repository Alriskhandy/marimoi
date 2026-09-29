<?php

namespace App\Http\Controllers;

use App\Models\Map;
use App\Models\MapPublication;
use App\Models\MapShare;
use App\Models\Opd;
use App\Models\SpatialLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Kelola Peta (docs/marimoi v2/04_implementation/11-plan-dashboard-skema-baru.md
 * Bagian B–C) — susun banyak spatial_layers jadi satu peta bernama, lalu terbitkan.
 * Domain logic (Map::publish()) sudah ada & sudah teruji (MapSharingTest); controller
 * ini murni memanggilnya dari HTTP.
 */
class MapController extends Controller
{
    public function index()
    {
        $maps = Map::withCount('layers')->orderByDesc('created_at')->get();

        return view('backend.pages.maps.index', compact('maps'));
    }

    public function create()
    {
        $opdOptions = Opd::orderBy('name')->get(['id', 'name', 'singkatan']);

        return view('backend.pages.maps.create', compact('opdOptions'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        $map = Map::create($validated + [
            'slug' => $this->uniqueSlug($validated['title']),
            'owner_user_id' => auth()->id(),
        ]);

        return redirect()->route('maps.edit', $map)->with('success', 'Peta berhasil dibuat. Susun layer di bawah.');
    }

    public function edit(Map $map)
    {
        $map->load(['layers.spatialLayer', 'publications' => fn ($q) => $q->orderByDesc('revision')->with('shares')]);
        $availableLayers = SpatialLayer::where('is_active', true)
            ->whereNotIn('id', $map->layers->pluck('spatial_layer_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'title', 'color']);

        return view('backend.pages.maps.edit', compact('map', 'availableLayers'));
    }

    public function update(Request $request, Map $map)
    {
        $map->update($this->validated($request));

        return redirect()->route('maps.edit', $map)->with('success', 'Peta berhasil diperbarui.');
    }

    public function destroy(Map $map)
    {
        $map->delete();

        return redirect()->route('maps.index')->with('success', 'Peta berhasil dihapus.');
    }

    public function publish(Map $map)
    {
        if ($map->layers()->count() === 0) {
            return back()->with('error', 'Peta belum punya layer — tambahkan minimal satu layer sebelum diterbitkan.');
        }

        $publication = $map->publish(auth()->user());

        return redirect()->route('maps.edit', $map)
            ->with('success', "Peta berhasil diterbitkan sebagai revisi #{$publication->revision}.");
    }

    /**
     * Bagian D.1: generate link berbagi. Token plaintext HANYA ada di response
     * ini — tidak pernah disimpan, tidak bisa ditampilkan ulang sesudahnya.
     */
    public function share(Request $request, Map $map, MapPublication $publication)
    {
        abort_unless($publication->map_id === $map->id, 404);

        $request->validate(['expires_in_days' => 'nullable|integer|min:1|max:365']);
        $expiresAt = $request->expires_in_days ? now()->addDays((int) $request->expires_in_days) : null;

        ['token' => $token] = MapShare::generateFor($publication, auth()->user(), $expiresAt);

        return redirect()->route('maps.edit', $map)
            ->with('shareToken', $token)
            ->with('shareUrl', route('map-shares.show', $token));
    }

    public function revokeShare(Map $map, MapShare $mapShare)
    {
        abort_unless($mapShare->publication->map_id === $map->id, 404);

        $mapShare->revoke();

        return redirect()->route('maps.edit', $map)->with('success', 'Link berbagi berhasil dicabut.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'owner_opd_id' => 'nullable|exists:opd,id',
            'visibility' => 'required|in:private,public',
            'zoom' => 'nullable|numeric|min:0|max:22',
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 1;

        while (Map::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
