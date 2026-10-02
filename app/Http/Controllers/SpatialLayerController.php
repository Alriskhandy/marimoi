<?php

namespace App\Http\Controllers;

use App\Models\MapType;
use App\Models\MapTypeDynamicAttribute;
use App\Models\SpatialLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * "Daftar Layer & Data" — skema v3 (docs/marimoi v2/db-schema-v3.md, plan
 * mellow-weaving-eclipse Fase 3). Layer TIDAK LAGI bertingkat antar-sesama
 * (parent_id v2 dihapus) — setiap Layer ditempatkan di satu Kategori/
 * Subkategori (categories_v3/category_nodes), yang menggantikan konsep
 * "Layer Induk" lama. Style (warna/ikon/marker/opacity) juga sudah pindah ke
 * tabel layer_styles terpisah — lihat validated() yang menulis ke kedua
 * tabel dalam satu transaksi supaya form admin tetap terasa satu kesatuan.
 */
class SpatialLayerController extends Controller
{
    public function index()
    {
        $layers = SpatialLayer::with(['mapType', 'categoryNode', 'defaultStyle'])
            ->withCount('features')
            ->orderBy('name')
            ->get();
        $mapTypes = MapType::active()->orderBy('nama')->get();
        $categoryPaths = $this->categoryPaths();
        [$categoryOptions, $categoryNodeOptions] = $this->categoryPickerOptions();

        return view('backend.pages.spatial-layers.index', compact('layers', 'mapTypes', 'categoryPaths', 'categoryOptions', 'categoryNodeOptions'));
    }

    public function create()
    {
        $mapTypes = MapType::active()->get();
        [$categoryOptions, $categoryNodeOptions] = $this->categoryPickerOptions();

        return view('backend.pages.spatial-layers.create', compact('mapTypes', 'categoryOptions', 'categoryNodeOptions'));
    }

    /**
     * Halaman detail (read-only + modal edit Informasi Layer) — tidak ada lagi
     * halaman edit terpisah, form ubah Layer ditampilkan lewat modal di halaman ini
     * sendiri (pola sama seperti modal edit Kategori di /dashboard/categories).
     */
    public function show(SpatialLayer $spatialLayer)
    {
        $spatialLayer->load(['mapType', 'categoryNode', 'defaultStyle', 'features.region']);
        $mapTypes = MapType::active()->get();
        $categoryPaths = $this->categoryPaths();
        [$categoryOptions, $categoryNodeOptions] = $this->categoryPickerOptions();

        $dynamicAttributes = $spatialLayer->map_type_id
            ? MapTypeDynamicAttribute::where('map_type_id', $spatialLayer->map_type_id)
                ->where('is_active', true)
                ->with('metadataDefinition')
                ->orderBy('urutan')
                ->get()
            : collect();

        return view('backend.pages.spatial-layers.show', [
            'layer' => $spatialLayer,
            'mapTypes' => $mapTypes,
            'categoryOptions' => $categoryOptions,
            'categoryNodeOptions' => $categoryNodeOptions,
            'categoryPaths' => $categoryPaths,
            'dynamicAttributes' => $dynamicAttributes,
        ]);
    }

    public function store(Request $request)
    {
        [$layerData, $styleData] = $this->validated($request);

        DB::transaction(function () use ($layerData, $styleData) {
            $layer = SpatialLayer::create($layerData);
            $style = $layer->styles()->create($styleData + ['name' => 'Default', 'style_type' => 'simple', 'is_default' => true]);
            $layer->update(['default_style_id' => $style->id]);
        });

        return redirect()->route('spatial-layers.index')->with('success', 'Layer berhasil dibuat.');
    }

    public function update(Request $request, SpatialLayer $spatialLayer)
    {
        [$layerData, $styleData] = $this->validated($request, $spatialLayer);

        DB::transaction(function () use ($spatialLayer, $layerData, $styleData) {
            $spatialLayer->update($layerData);

            $style = $spatialLayer->styles()->where('is_default', true)->first();

            if ($style) {
                $style->update($styleData);
            } else {
                $style = $spatialLayer->styles()->create($styleData + ['name' => 'Default', 'style_type' => 'simple', 'is_default' => true]);
                $spatialLayer->update(['default_style_id' => $style->id]);
            }
        });

        return redirect()->route('spatial-layers.show', $spatialLayer)->with('success', 'Layer berhasil diperbarui.');
    }

    public function destroy(SpatialLayer $spatialLayer)
    {
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
            'ids.*' => ['required', 'uuid', 'exists:layers,id'],
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

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} [$layerData, $styleData]
     */
    private function validated(Request $request, ?SpatialLayer $spatialLayer = null): array
    {
        $validated = $request->validate([
            'map_type_id' => 'nullable|exists:map_types,id',
            'category_id' => ['required', 'uuid', 'exists:categories_v3,id'],
            'category_node_id' => ['nullable', 'uuid', 'exists:category_nodes,id'],
            'name' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'color' => 'nullable|string|max:25',
            'icon' => 'nullable|string|max:255',
            'default_opacity' => 'nullable|numeric|min:0|max:1',
            'is_marker' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (! empty($validated['category_node_id'])) {
            $belongsToCategory = DB::table('category_nodes')
                ->where('id', $validated['category_node_id'])
                ->where('category_id', $validated['category_id'])
                ->exists();

            if (! $belongsToCategory) {
                throw ValidationException::withMessages([
                    'category_node_id' => 'Subkategori tidak sesuai dengan Kategori yang dipilih.',
                ]);
            }
        }

        $isActive = (bool) ($validated['is_active'] ?? false);
        $opacity = $validated['default_opacity'] ?? 1;

        $layerData = [
            'category_id' => $validated['category_id'],
            'category_node_id' => $validated['category_node_id'] ?? null,
            'map_type_id' => $validated['map_type_id'] ?? null,
            'layer_type_id' => $spatialLayer?->layer_type_id ?? 4,
            'code' => $spatialLayer?->code ?? $this->uniqueCode(),
            'name' => $validated['name'],
            'slug' => $spatialLayer?->slug ?? $this->uniqueSlug($validated['name']),
            'short_description' => $validated['short_description'] ?? null,
            'visibility' => $spatialLayer?->visibility ?? 'public',
            'status' => $isActive ? 'published' : 'draft',
            'published_at' => $isActive ? ($spatialLayer?->published_at ?? now()) : null,
            'default_opacity' => $opacity,
        ];

        $styleData = [
            'definition' => [
                'color' => $validated['color'] ?? '#2563eb',
                'icon' => $validated['icon'] ?? null,
                'is_marker' => (bool) ($validated['is_marker'] ?? false),
                'opacity' => $opacity,
            ],
        ];

        return [$layerData, $styleData];
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

    private function uniqueCode(): string
    {
        do {
            $code = 'layer-'.Str::lower(Str::random(10));
        } while (SpatialLayer::where('code', $code)->exists());

        return $code;
    }

    /**
     * Daftar kategori root (categories_v3) & subkategori (category_nodes)
     * untuk pemilih "Kategori"/"Subkategori" di form Layer — menggantikan
     * picker "Layer Induk" v2. Bukan lewat model `Category` (masih dipakai
     * tabel lama, lihat catatan di CategoryNode) — query langsung ke tabel.
     *
     * @return array{0: Collection, 1: Collection}
     */
    private function categoryPickerOptions(): array
    {
        $categoryOptions = DB::table('categories_v3')
            ->where('code', '!=', 'uncategorized')
            ->orderBy('name')
            ->get(['id', 'name']);

        $categoryNodeOptions = DB::table('category_nodes')
            ->orderBy('depth')
            ->orderBy('name')
            ->get(['id', 'name', 'category_id', 'depth']);

        return [$categoryOptions, $categoryNodeOptions];
    }

    /**
     * Breadcrumb "Kategori › Subkategori" per layer untuk ditampilkan di
     * kolom tabel index — dibangun sekali di PHP (bukan N+1 query per baris).
     *
     * @return array<string, string> keyed by "category_id" atau "category_node_id"
     */
    private function categoryPaths(): array
    {
        $categories = DB::table('categories_v3')->pluck('name', 'id');
        $nodes = DB::table('category_nodes')->get(['id', 'name', 'category_id', 'parent_id']);

        $paths = [];

        foreach ($categories as $id => $name) {
            $paths['cat:'.$id] = $name;
        }

        $nodesById = $nodes->keyBy('id');

        foreach ($nodes as $node) {
            $segments = [$node->name];
            $cursor = $node;

            while ($cursor->parent_id && $nodesById->has($cursor->parent_id)) {
                $cursor = $nodesById->get($cursor->parent_id);
                array_unshift($segments, $cursor->name);
            }

            array_unshift($segments, $categories[$node->category_id] ?? '-');
            $paths['node:'.$node->id] = implode(' › ', $segments);
        }

        return $paths;
    }
}
