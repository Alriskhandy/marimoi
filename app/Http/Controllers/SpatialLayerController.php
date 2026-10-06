<?php

namespace App\Http\Controllers;

use App\Models\LayerType;
use App\Models\Opd;
use App\Models\SpatialLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
        $query = SpatialLayer::with(['categoryNode', 'defaultStyle', 'opd', 'metadata'])
            ->withCount('features');

        if ($this->isAdminOpd()) {
            $query->where('opd_id', $this->currentOpdId());
        }

        $layers = $query->orderBy('name')->get();
        $layerTypes = LayerType::orderBy('name')->get();
        $opds = Opd::orderBy('name')->get(['id', 'name', 'singkatan']);
        $categoryPaths = $this->categoryPaths();
        [$categoryOptions, $categoryNodeOptions] = $this->categoryPickerOptions();

        // Draft wizard "Tambah Layer" yang belum tuntas (plan
        // rippling-frolicking-ladybug) — ditampilkan sebagai banner terpisah
        // di atas daftar supaya mudah ditemukan, selain badge+tombol
        // "Lanjutkan" per baris di tabel utama (keduanya, bukan salah satu).
        $wizardDraftsQuery = SpatialLayer::whereNotNull('wizard_step');
        if ($this->isAdminOpd()) {
            $wizardDraftsQuery->where('opd_id', $this->currentOpdId());
        }
        $wizardDrafts = $wizardDraftsQuery->orderByDesc('updated_at')->get();

        return view('backend.pages.spatial-layers.index', compact('layers', 'layerTypes', 'opds', 'categoryPaths', 'categoryOptions', 'categoryNodeOptions', 'wizardDrafts'));
    }

    /**
     * Halaman detail (read-only + modal edit Informasi Layer) — tidak ada lagi
     * halaman edit terpisah, form ubah Layer ditampilkan lewat modal di halaman ini
     * sendiri (pola sama seperti modal edit Kategori di /dashboard/categories).
     */
    public function show(SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        $spatialLayer->load(['layerType', 'categoryNode', 'defaultStyle', 'opd', 'features.region']);
        $layerTypes = LayerType::orderBy('name')->get();
        $opds = $this->canAssignOpd() ? Opd::orderBy('name')->get(['id', 'name', 'singkatan']) : collect();
        $categoryPaths = $this->categoryPaths();
        [$categoryOptions, $categoryNodeOptions] = $this->categoryPickerOptions();

        // Layer tidak lagi punya map_type_id (lihat migration
        // drop_map_type_id_and_visibility_from_layers_table) — Metadata
        // Dinamis per Jenis Peta selalu kosong sekarang, tidak ada lagi yang
        // bisa diresolusi.
        $dynamicAttributes = collect();

        return view('backend.pages.spatial-layers.show', [
            'layer' => $spatialLayer,
            'layerTypes' => $layerTypes,
            'opds' => $opds,
            'categoryOptions' => $categoryOptions,
            'categoryNodeOptions' => $categoryNodeOptions,
            'categoryPaths' => $categoryPaths,
            'dynamicAttributes' => $dynamicAttributes,
        ]);
    }

    /**
     * Pembuatan Layer baru pindah ke LayerWizardController::store() (wizard
     * "Tambah Layer" 4 tahap, plan rippling-frolicking-ladybug) — method ini
     * sengaja dihapus, bukan dipertahankan sebagai jalur mati, supaya tidak
     * ada dua cara berbeda membuat Layer yang bisa diam-diam saling
     * menyimpang. `update()` di bawah masih memakai validated() yang sama.
     */
    public function update(Request $request, SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

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

    /**
     * Ubah status Layer (draft/published/archived) — satu-satunya jalur yang
     * boleh mempublikasikan Layer (R17), dipisah dari update() agar bisa
     * dibatasi permission `spatial-layers.publish` secara independen dari
     * `spatial-layers.edit` yang juga dimiliki admin-opd.
     */
    public function updateStatus(Request $request, SpatialLayer $spatialLayer)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        // Fitur "Sumber Layer" (layanan eksternal WMS/WMTS/XYZ/ArcGIS/COG)
        // dihapus (2026-10-06, migration drop_layer_sources_table) — data
        // spasial sekarang HANYA lewat impor file, jadi syarat publish sama
        // untuk semua jenis Layer (tidak ada lagi percabangan stores_features).
        if ($validated['status'] === 'published') {
            if (! $spatialLayer->features()->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Layer belum punya Data Spasial, tidak dapat dipublikasikan.',
                ]);
            }

            if (! $spatialLayer->default_style_id) {
                throw ValidationException::withMessages([
                    'status' => 'Layer belum punya style default, tidak dapat dipublikasikan.',
                ]);
            }
        }

        $spatialLayer->update([
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published' ? ($spatialLayer->published_at ?? now()) : null,
        ]);

        return redirect()->route('spatial-layers.show', $spatialLayer)->with('success', 'Status Layer berhasil diubah.');
    }

    public function destroy(SpatialLayer $spatialLayer)
    {
        $this->authorizeOpdAccess($spatialLayer);

        if ($spatialLayer->features()->exists()) {
            return redirect()->back()->with('error', 'Layer tidak dapat dihapus karena masih punya Data Spasial.');
        }

        $spatialLayer->delete();

        return redirect()->route('spatial-layers.index')->with('success', 'Layer berhasil dihapus.');
    }

    /**
     * Tolak akses admin-opd ke Layer milik OPD lain atau tanpa OPD (R19).
     * Peran lain (super-admin/admin-bappeda) selalu lolos.
     */
    private function authorizeOpdAccess(SpatialLayer $spatialLayer): void
    {
        if ($this->isAdminOpd() && $spatialLayer->opd_id !== $this->currentOpdId()) {
            abort(403, 'Anda tidak memiliki akses ke Layer milik OPD lain.');
        }
    }

    private function isAdminOpd(): bool
    {
        return Auth::user()?->role?->slug === 'admin-opd';
    }

    /**
     * super-admin/admin-bappeda boleh memilih/memindahkan opd_id bebas (R21);
     * admin-opd tidak (opd_id-nya selalu dari dirinya sendiri, lihat R20).
     */
    private function canAssignOpd(): bool
    {
        return ! $this->isAdminOpd();
    }

    private function currentOpdId(): ?int
    {
        return Auth::user()?->opd_id;
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} [$layerData, $styleData]
     */
    private function validated(Request $request, ?SpatialLayer $spatialLayer = null): array
    {
        $validated = $request->validate([
            'layer_type_id' => 'nullable|exists:layer_types,id',
            'opd_id' => 'nullable|exists:opd,id',
            'category_id' => ['required', 'uuid', 'exists:categories_v3,id'],
            'category_node_id' => ['nullable', 'uuid', 'exists:category_nodes,id'],
            'name' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'color' => 'nullable|string|max:25',
            'icon' => 'nullable|string|max:255',
            'default_opacity' => 'nullable|numeric|min:0|max:1',
            'is_marker' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
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

        // R20/R21: admin-opd tidak bisa memilih opd_id bebas — selalu OPD-nya
        // sendiri. super-admin/admin-bappeda boleh pilih/pindahkan bebas.
        $opdId = $this->canAssignOpd() ? ($validated['opd_id'] ?? $spatialLayer?->opd_id) : $this->currentOpdId();

        // R17: hanya pemegang permission `spatial-layers.publish` yang boleh
        // mengubah status lewat form ini. Layer baru selalu DRAFT (R20);
        // layer existing yang statusnya diubah lewat form oleh user tanpa
        // permission publish, statusnya dipertahankan apa adanya.
        $canPublish = (bool) Auth::user()?->can('spatial-layers.publish');
        $isActive = (bool) ($validated['is_active'] ?? false);
        $opacity = $validated['default_opacity'] ?? 1;

        if ($spatialLayer === null) {
            $status = ($canPublish && $isActive) ? 'published' : 'draft';
            $publishedAt = $status === 'published' ? now() : null;
        } elseif ($canPublish) {
            $status = $isActive ? 'published' : 'draft';
            $publishedAt = $status === 'published' ? ($spatialLayer->published_at ?? now()) : null;
        } else {
            $status = $spatialLayer->status;
            $publishedAt = $spatialLayer->published_at;
        }

        $layerData = [
            'category_id' => $validated['category_id'],
            'category_node_id' => $validated['category_node_id'] ?? null,
            'opd_id' => $opdId,
            'layer_type_id' => $validated['layer_type_id'] ?? $spatialLayer?->layer_type_id ?? 4,
            'code' => $spatialLayer?->code ?? $this->uniqueCode(),
            'name' => $validated['name'],
            'slug' => $spatialLayer?->slug ?? $this->uniqueSlug($validated['name']),
            'short_description' => $validated['short_description'] ?? null,
            'status' => $status,
            'published_at' => $publishedAt,
            'default_opacity' => $opacity,
            'sort_order' => $validated['sort_order'] ?? $spatialLayer?->sort_order ?? 0,
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
        // Sebelumnya menyembunyikan kategori sentinel "Uncategorized" lewat
        // `where('code', '!=', 'uncategorized')` — kolom `code` dihapus dari
        // categories_v3 (2026-10-06), jadi sentinel itu sekarang ikut muncul
        // di dropdown seperti kategori biasa (belum diganti penggantinya).
        $categoryOptions = DB::table('categories_v3')
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
