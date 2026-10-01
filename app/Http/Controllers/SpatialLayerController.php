<?php

namespace App\Http\Controllers;

use App\Models\MapType;
use App\Models\SpatialLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * "Daftar Layer & Data" — Layer (docs/marimoi v2/04_implementation/
 * 12-implementasi-perbaikan-pemetaan.md Bagian 3.2). Tree parent-child TANPA batas
 * kedalaman (Keputusan #3) — satu-satunya validasi wajib adalah cegah cycle.
 */
class SpatialLayerController extends Controller
{
    public function index()
    {
        $layers = SpatialLayer::with('mapType')->withCount(['children', 'features'])->orderBy('name')->get();
        $roots = $layers->whereNull('parent_id')->values();
        $mapTypes = MapType::active()->orderBy('nama')->get();

        return view('backend.pages.spatial-layers.index', compact('layers', 'roots', 'mapTypes'));
    }

    public function create()
    {
        $mapTypes = MapType::active()->get();
        $parentOptions = SpatialLayer::orderBy('name')->get(['id', 'name']);

        return view('backend.pages.spatial-layers.create', compact('mapTypes', 'parentOptions'));
    }

    /**
     * Halaman detail (read-only + modal edit Informasi Layer) — tidak ada lagi
     * halaman edit terpisah, form ubah Layer ditampilkan lewat modal di halaman ini
     * sendiri (pola sama seperti modal edit Kategori di /dashboard/categories).
     */
    public function show(SpatialLayer $spatialLayer)
    {
        $spatialLayer->load(['mapType', 'parent', 'children', 'features.region']);
        $mapTypes = MapType::active()->get();
        $parentOptions = SpatialLayer::where('id', '!=', $spatialLayer->id)->orderBy('name')->get(['id', 'name']);

        return view('backend.pages.spatial-layers.show', ['layer' => $spatialLayer, 'mapTypes' => $mapTypes, 'parentOptions' => $parentOptions]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        SpatialLayer::create($validated);

        return redirect()->route('spatial-layers.index')->with('success', 'Layer berhasil dibuat.');
    }

    public function update(Request $request, SpatialLayer $spatialLayer)
    {
        $validated = $this->validated($request, $spatialLayer);

        $spatialLayer->update($validated);

        return redirect()->route('spatial-layers.show', $spatialLayer)->with('success', 'Layer berhasil diperbarui.');
    }

    public function destroy(SpatialLayer $spatialLayer)
    {
        if ($spatialLayer->children()->exists()) {
            return redirect()->back()->with('error', 'Layer tidak dapat dihapus karena masih punya Layer anak.');
        }

        if ($spatialLayer->features()->exists()) {
            return redirect()->back()->with('error', 'Layer tidak dapat dihapus karena masih punya Data Spasial.');
        }

        $spatialLayer->delete();

        return redirect()->route('spatial-layers.index')->with('success', 'Layer berhasil dihapus.');
    }

    /**
     * Ubah Jenis Peta untuk beberapa Layer sekaligus (pola sama dengan bulk update
     * kategori/layer di halaman Data Spasial).
     */
    public function bulkUpdateMapType(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:spatial_layers,id',
            'map_type_id' => 'required|exists:map_types,id',
        ], [
            'ids.required' => 'Tidak ada Layer yang dipilih.',
            'map_type_id.required' => 'Jenis Peta tujuan harus dipilih.',
            'map_type_id.exists' => 'Jenis Peta tidak valid.',
        ]);

        $updatedCount = SpatialLayer::whereIn('id', $validated['ids'])
            ->update(['map_type_id' => $validated['map_type_id']]);

        return redirect()->route('spatial-layers.index')
            ->with('success', "Berhasil mengubah Jenis Peta untuk {$updatedCount} Layer.");
    }

    private function validated(Request $request, ?SpatialLayer $spatialLayer = null): array
    {
        $validated = $request->validate([
            'map_type_id' => 'required|exists:map_types,id',
            'parent_id' => 'nullable|exists:spatial_layers,id',
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'layer_class' => ['required', Rule::in(['thematic', 'development'])],
            'color' => 'nullable|string|max:25',
            'icon' => 'nullable|string|max:255',
            'opacity' => 'nullable|numeric|min:0|max:1',
            'is_marker' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($spatialLayer && $spatialLayer->wouldCreateCycle($validated['parent_id'] ?? null)) {
            throw ValidationException::withMessages([
                'parent_id' => 'Layer induk tidak boleh Layer ini sendiri atau salah satu keturunannya (akan membentuk cycle).',
            ]);
        }

        $validated['title'] = $validated['title'] ?? $validated['name'];
        $validated['slug'] = $spatialLayer?->slug ?? $this->uniqueSlug($validated['name']);

        return $validated;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (SpatialLayer::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
