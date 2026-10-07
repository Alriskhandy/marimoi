<?php

namespace App\Http\Controllers;

use App\Models\LayerType;
use App\Models\Opd;
use App\Models\SpatialLayer;
use App\Models\SpatialLayerMetadata;
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

        $spatialLayer->load(['layerType', 'categoryNode', 'defaultStyle', 'opd', 'metadata', 'features.region']);
        $layerTypes = LayerType::orderBy('name')->get();
        $opds = $this->canAssignOpd() ? Opd::orderBy('name')->get(['id', 'name', 'singkatan']) : collect();
        $categoryPaths = $this->categoryPaths();
        [$categoryOptions, $categoryNodeOptions] = $this->categoryPickerOptions();

        // Layer tidak lagi punya map_type_id (lihat migration
        // drop_map_type_id_and_visibility_from_layers_table) — Metadata
        // Dinamis per Jenis Peta selalu kosong sekarang, tidak ada lagi yang
        // bisa diresolusi.
        $dynamicAttributes = collect();

        // Pilihan Layer tujuan untuk "Pindah ke Layer Lain" (bulk Data
        // Spasial terpilih, SpatialLayerFeatureController::bulkMoveToLayer())
        // — admin-opd hanya boleh memindahkan ke Layer OPD-nya sendiri, sama
        // seperti authorizeOpdAccess() yang akan dicek ulang di server.
        $moveTargetLayersQuery = SpatialLayer::where('id', '!=', $spatialLayer->id);
        if ($this->isAdminOpd()) {
            $moveTargetLayersQuery->where('opd_id', $this->currentOpdId());
        }
        $moveTargetLayers = $moveTargetLayersQuery->orderBy('name')->get(['id', 'name']);

        return view('backend.pages.spatial-layers.show', [
            'layer' => $spatialLayer,
            'layerTypes' => $layerTypes,
            'opds' => $opds,
            'categoryOptions' => $categoryOptions,
            'categoryNodeOptions' => $categoryNodeOptions,
            'categoryPaths' => $categoryPaths,
            'dynamicAttributes' => $dynamicAttributes,
            'moveTargetLayers' => $moveTargetLayers,
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

        [$layerData, $metadataData] = $this->validated($request, $spatialLayer);

        DB::transaction(function () use ($spatialLayer, $layerData, $metadataData) {
            $spatialLayer->update($layerData);

            $metadata = SpatialLayerMetadata::firstOrNew(['layer_id' => $spatialLayer->id]);
            $metadata->fill($metadataData);
            $metadata->save();
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
     * Hapus massal dari bulk-selection di index — admin-opd hanya bisa
     * menghapus Layer OPD-nya sendiri (id Layer OPD lain di `ids[]` diabaikan
     * diam-diam, bukan 403 total, karena ini aksi massal atas banyak baris
     * yang mungkin dipilih sekaligus). Layer yang masih punya Data Spasial
     * dilewati (aturan sama seperti destroy() tunggal), bukan menggagalkan
     * seluruh batch.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        $query = SpatialLayer::whereIn('id', $validated['ids'])->withCount('features');

        if ($this->isAdminOpd()) {
            $query->where('opd_id', $this->currentOpdId());
        }

        $layers = $query->get();
        $deleted = 0;
        $skipped = 0;

        foreach ($layers as $layer) {
            if ($layer->features_count > 0) {
                $skipped++;

                continue;
            }

            $layer->delete();
            $deleted++;
        }

        $message = "{$deleted} Layer berhasil dihapus.";
        if ($skipped > 0) {
            $message .= " {$skipped} Layer dilewati karena masih punya Data Spasial.";
        }

        return redirect()->route('spatial-layers.index')
            ->with($deleted > 0 ? 'success' : 'error', $message);
    }

    /**
     * Pindah Kategori/Sub Kategori massal dari bulk-selection di index — sama
     * seperti bulkDestroy(), id Layer OPD lain (untuk admin-opd) diabaikan
     * diam-diam dari scope update, bukan 403 total.
     */
    public function bulkUpdateCategory(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
            'category_id' => ['required', 'uuid', 'exists:categories_v3,id'],
            'category_node_id' => ['nullable', 'uuid', 'exists:category_nodes,id'],
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

        $query = SpatialLayer::whereIn('id', $validated['ids']);

        if ($this->isAdminOpd()) {
            $query->where('opd_id', $this->currentOpdId());
        }

        $count = $query->update([
            'category_id' => $validated['category_id'],
            'category_node_id' => $validated['category_node_id'] ?? null,
        ]);

        return redirect()->route('spatial-layers.index')
            ->with($count > 0 ? 'success' : 'error', "{$count} Layer berhasil dipindahkan ke kategori baru.");
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
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} [$layerData, $metadataData]
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
            'data_year' => 'nullable|integer|min:1900|max:2100',
            'sumber_data' => 'nullable|string|max:255',
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

        // R17: status (draft/published/archived) TIDAK disentuh di sini sama
        // sekali — satu-satunya jalur ubah status adalah updateStatus()
        // (permission `spatial-layers.publish` sendiri, lihat route
        // spatial-layers.update-status), supaya edit info dasar Layer tidak
        // bisa diam-diam mengubah status lewat checkbox yang mudah terlewat.
        //
        // Warna/opacity/marker/urutan tampil juga TIDAK disentuh di sini —
        // style (warna/ikon/marker/opacity) sudah punya halaman tersendiri
        // (/styles, lihat LayerStyleController), jadi form ini murni info
        // dasar Layer supaya tidak ada dua form berbeda yang bisa saling
        // menimpa style yang sama.
        $layerData = [
            'category_id' => $validated['category_id'],
            'category_node_id' => $validated['category_node_id'] ?? null,
            'opd_id' => $opdId,
            'layer_type_id' => $validated['layer_type_id'] ?? $spatialLayer?->layer_type_id ?? 4,
            'code' => $spatialLayer?->code ?? $this->uniqueCode(),
            'name' => $validated['name'],
            'slug' => $spatialLayer?->slug ?? $this->uniqueSlug($validated['name']),
            'short_description' => $validated['short_description'] ?? null,
        ];

        $metadataData = [
            'data_year' => $validated['data_year'] ?? null,
            'sumber_data' => $validated['sumber_data'] ?? null,
        ];

        return [$layerData, $metadataData];
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
            ->get(['id', 'name', 'category_id', 'depth', 'parent_id']);

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
