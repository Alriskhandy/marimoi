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

        return view('backend.pages.spatial-layers.index', compact('layers', 'roots'));
    }

    public function create()
    {
        $mapTypes = MapType::active()->get();
        $parentOptions = SpatialLayer::orderBy('name')->get(['id', 'name']);

        return view('backend.pages.spatial-layers.create', compact('mapTypes', 'parentOptions'));
    }

    /**
     * Halaman detail (read-only) — terpisah dari edit(), untuk melihat Layer
     * lengkap dengan Metadata Utama Jenis-nya, keturunan tree, dan ringkasan Data
     * Spasial tanpa perlu masuk mode ubah.
     */
    public function show(SpatialLayer $spatialLayer)
    {
        $spatialLayer->load(['mapType.opdPenanggungJawab', 'parent', 'children', 'features']);

        return view('backend.pages.spatial-layers.show', ['layer' => $spatialLayer]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        SpatialLayer::create($validated);

        return redirect()->route('spatial-layers.index')->with('success', 'Layer berhasil dibuat.');
    }

    public function edit(SpatialLayer $spatialLayer)
    {
        $mapTypes = MapType::active()->get();
        $parentOptions = SpatialLayer::where('id', '!=', $spatialLayer->id)->orderBy('name')->get(['id', 'name']);

        return view('backend.pages.spatial-layers.edit', ['layer' => $spatialLayer, 'mapTypes' => $mapTypes, 'parentOptions' => $parentOptions]);
    }

    public function update(Request $request, SpatialLayer $spatialLayer)
    {
        $validated = $this->validated($request, $spatialLayer);

        $spatialLayer->update($validated);

        return redirect()->route('spatial-layers.edit', $spatialLayer)->with('success', 'Layer berhasil diperbarui.');
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
