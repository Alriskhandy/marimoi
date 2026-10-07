<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SpatialLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Hanya method yang benar-benar diroutekan & dikonsumsi UI yang dipertahankan
 * di sini (index/store/update/destroy/getOptions) — lihat routes/backend.php.
 * Method lama (create/edit/show tanpa view, getByType/getTree/toggleActive/
 * getActiveByType/getStatistics/bulkUpdate/duplicate/move/export/import) sudah
 * tidak diroutekan atau tidak dipanggil JS mana pun, jadi dibuang saat rewrite
 * ke skema v3 (plan mellow-weaving-eclipse, Fase 3 lanjutan) alih-alih ditulis
 * ulang untuk struktur dua-tabel (categories_v3/category_nodes) tanpa manfaat.
 *
 * Sejak 2026-10-06, kategori tidak lagi punya `type`/`is_active`/`icon`/
 * `color`/`gambar`/`is_marker` sendiri (lihat migration
 * drop_display_and_type_columns_from_categories_v3_and_category_nodes) —
 * pengelompokan jenis sudah tersedia lewat `layers.map_type_id` ->
 * `map_types`, dan gaya tampil (ikon/warna/marker) sudah sepenuhnya milik
 * `layer_styles` per-Layer. Kategori sekarang murni struktur hirarki
 * (nama/deskripsi/parent/urutan).
 */
class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // "data_spatial_count" di sini bukan relasi ke tabel data_spatial
        // lama (kategori baru tidak pernah py FK ke sana) — melainkan jumlah
        // fitur v3 (layers.feature_count) milik Layer yang terhubung ke
        // kategori ini, lewat category_id (root) atau category_node_id (turunan).
        // "layers_count" adalah jumlah baris Layer itu sendiri (dipakai badge
        // "X layer" di panel pohon taksonomi), bukan jumlah fitur/data di
        // dalamnya.
        $categories = Category::query()
            ->selectRaw(<<<'SQL'
                categories_tree_v3.*,
                (
                    SELECT COALESCE(SUM(feature_count), 0) FROM layers
                    WHERE layers.category_id = categories_tree_v3.id
                       OR layers.category_node_id = categories_tree_v3.id
                ) AS data_spatial_count,
                (
                    SELECT COUNT(*) FROM layers
                    WHERE layers.category_id = categories_tree_v3.id
                       OR layers.category_node_id = categories_tree_v3.id
                ) AS layers_count
            SQL)
            ->with(['children.children']) // Load up to 3 levels
            ->orderBy('parent_id', 'asc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('nama', 'asc')
            ->get();

        // Layer yang ditempel LANGSUNG ke tiap kategori/subkategori (bukan
        // agregat turunan seperti data_spatial_count/layers_count di atas) —
        // dipakai panel detail kanan untuk menampilkan daftar Layer milik
        // kategori yang sedang dipilih. Key "cat:{id}"/"node:{id}" mengikuti
        // konvensi $categoryKey yang sudah dipakai halaman ini (lihat tombol
        // "Lihat Layer di kategori ini") & filter spatial-layers/index.
        $categoryIds = $categories->pluck('id');
        $layersByCategory = SpatialLayer::query()
            ->with(['opd', 'metadata'])
            ->where(function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds)
                    ->orWhereIn('category_node_id', $categoryIds);
            })
            ->orderBy('name')
            ->get()
            ->groupBy(fn (SpatialLayer $layer) => $layer->category_node_id
                ? 'node:'.$layer->category_node_id
                : 'cat:'.$layer->category_id);

        $categories->each(function (Category $category) use ($layersByCategory) {
            $key = ($category->parent_id === null ? 'cat:' : 'node:').$category->id;
            $category->setAttribute('direct_layers', $layersByCategory->get($key, collect()));
        });

        // Leluhur kategori utama (root) tiap baris — dipakai panel detail
        // kanan untuk mengisi category_id saat prefill link "Tambah Layer"
        // (layers_v3.category_id selalu root, lihat catatan di SpatialLayer).
        $byId = $categories->keyBy('id');
        $categories->each(function (Category $category) use ($byId) {
            $rootId = $category->id;
            $current = $category;
            $guard = 0;

            while ($current->parent_id !== null && $guard < 20) {
                $current = $byId->get($current->parent_id);
                if (! $current) {
                    break;
                }
                $rootId = $current->id;
                $guard++;
            }

            $category->setAttribute('root_id', $rootId);
        });

        return view('backend.pages.categories.index', compact('categories'));
    }

    /**
     * Validasi parent untuk hirarki 3 level
     */
    private function validateParentHierarchy(string $parentId, ?string $currentCategoryId = null): bool
    {
        $parent = Category::find($parentId);
        if (! $parent) {
            return false;
        }

        // Parent tidak boleh lebih dari depth 1 (karena akan jadi depth 2, dan
        // anaknya jadi depth 2+1=3 yang sudah di luar batas 3 level).
        if ($parent->depth >= 2) {
            return false;
        }

        if ($currentCategoryId) {
            $childrenIds = [];
            $this->getChildrenIds(Category::find($currentCategoryId), $childrenIds);
            if (in_array($parentId, $childrenIds)) {
                return false;
            }
        }

        return true;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories_tree_v3,id',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'nama.required' => 'Nama kategori harus diisi',
            'nama.max' => 'Nama kategori maksimal 255 karakter',
            'parent_id.exists' => 'Kategori induk tidak ditemukan',
        ]);

        if ($validator->fails()) {
            return $this->failed($request, $validator->errors()->toArray());
        }

        if ($request->parent_id && ! $this->validateParentHierarchy($request->parent_id)) {
            return $this->failed($request, ['parent_id' => ['Kategori ini tidak dapat dijadikan parent. Maksimal 3 level hirarki (Parent → Child → Grandchild).']]);
        }

        try {
            $category = Category::create([
                'user_id' => Auth::id(),
                'nama' => $request->nama,
                'deskripsi' => $request->deskripsi,
                'parent_id' => $request->parent_id,
                'sort_order' => $request->input('sort_order', 0),
            ]);

            Log::info('Category created successfully', [
                'id' => $category->id,
                'nama' => $category->nama,
                'parent_id' => $category->parent_id,
            ]);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Kategori berhasil dibuat', 'data' => $category]);
            }

            return redirect()->route('categories.index')
                ->with('success', 'Kategori berhasil dibuat');
        } catch (\Exception $e) {
            Log::error('Error creating category: '.$e->getMessage());

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat membuat kategori: '.$e->getMessage()], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat membuat kategori: '.$e->getMessage()])
                ->withInput();
        }
    }

    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);
        $user = Auth::user();
        $userRole = $user->role->slug ?? null;

        if (! in_array($userRole, ['super-admin', 'admin-bappeda']) && $category->user_id !== $user->id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mengedit kategori ini.');
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories_tree_v3,id',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'nama.required' => 'Nama kategori harus diisi',
            'nama.max' => 'Nama kategori maksimal 255 karakter',
            'parent_id.exists' => 'Kategori induk tidak ditemukan',
        ]);

        if ($validator->fails()) {
            return $this->failed($request, $validator->errors()->toArray());
        }

        if ($request->parent_id) {
            if ($request->parent_id == $category->id) {
                return $this->failed($request, ['parent_id' => ['Kategori tidak boleh menjadi induk dari dirinya sendiri']]);
            }

            $childrenIds = [];
            $this->getChildrenIds($category, $childrenIds);
            if (in_array($request->parent_id, $childrenIds)) {
                return $this->failed($request, ['parent_id' => ['Kategori induk tidak boleh merupakan anak dari kategori ini']]);
            }

            if (! $this->validateParentHierarchy($request->parent_id, $category->id)) {
                return $this->failed($request, ['parent_id' => ['Kategori ini tidak dapat dijadikan parent. Maksimal 3 level hirarki (Parent → Child → Grandchild).']]);
            }

            $parentDepth = Category::find($request->parent_id)->depth;
            if ($this->hasGrandchildren($category) && $parentDepth >= 1) {
                return $this->failed($request, ['parent_id' => ['Kategori ini memiliki sub-kategori level 3. Tidak dapat dipindahkan ke level yang lebih dalam.']]);
            }
        }

        try {
            $category->update([
                'nama' => $request->nama,
                'deskripsi' => $request->deskripsi,
                'parent_id' => $request->parent_id,
                'sort_order' => $request->filled('sort_order') ? $request->input('sort_order') : $category->sort_order,
            ]);

            Log::info('Category updated successfully', [
                'id' => $category->id,
                'nama' => $category->nama,
                'parent_id' => $category->parent_id,
            ]);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Kategori berhasil diperbarui', 'data' => $category]);
            }

            return redirect()->route('categories.index')
                ->with('success', 'Kategori berhasil diperbarui');
        } catch (\Exception $e) {
            Log::error('Error updating category: '.$e->getMessage());

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memperbarui kategori: '.$e->getMessage()], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat memperbarui kategori: '.$e->getMessage()])
                ->withInput();
        }
    }

    private function hasGrandchildren(Category $category): bool
    {
        foreach (Category::where('parent_id', $category->id)->get() as $child) {
            if (Category::where('parent_id', $child->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function getChildrenIds(Category $category, array &$childrenIds): void
    {
        foreach (Category::where('parent_id', $category->id)->get() as $child) {
            $childrenIds[] = $child->id;
            $this->getChildrenIds($child, $childrenIds);
        }
    }

    /**
     * §5.1 butir 5 — pesan hapus-aman menyebut jumlah & nama penghalang,
     * bukan sekadar "masih memiliki sub-kategori"/"masih digunakan".
     */
    private function blockedByChildrenMessage(Category $category): string
    {
        $names = $category->children->pluck('nama');
        $shown = $names->take(5)->implode(', ');
        $suffix = $names->count() > 5 ? ', dst.' : '';

        return "Kategori tidak dapat dihapus karena masih memiliki {$names->count()} sub-kategori: {$shown}{$suffix}";
    }

    private function blockedByLayersMessage(Category $category): string
    {
        // whereNull('deleted_at') — lihat catatan di Category::hasLinkedLayers(),
        // query mentah ini tidak otomatis menyaring Layer yang sudah di-soft-delete.
        $names = DB::table('layers')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($category) {
                $query->where('category_id', $category->id)
                    ->orWhere('category_node_id', $category->id);
            })
            ->pluck('name');
        $shown = $names->take(5)->implode(', ');
        $suffix = $names->count() > 5 ? ', dst.' : '';

        return "Kategori tidak dapat dihapus karena masih dipakai {$names->count()} Layer: {$shown}{$suffix}";
    }

    public function destroy(string $id)
    {
        $category = Category::with('children')->findOrFail($id);
        $user = Auth::user();
        $userRole = $user->role->slug ?? null;

        if (! in_array($userRole, ['super-admin', 'admin-bappeda']) && $category->user_id !== $user->id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menghapus kategori ini.');
        }

        if ($category->children->count() > 0) {
            return redirect()->back()->with('error', $this->blockedByChildrenMessage($category));
        }

        if ($category->hasLinkedLayers()) {
            return redirect()->back()->with('error', $this->blockedByLayersMessage($category));
        }

        // hasLinkedLayers() di atas mengabaikan Layer yang sudah di-soft-delete
        // (lihat docblock-nya) — tapi baris Layer itu SENDIRI masih ada di DB
        // dengan category_id/category_node_id masih menunjuk ke sini, dan FK
        // `layers_v3_category_id_foreign` ON DELETE NO ACTION, jadi DELETE
        // Kategori/Subkategori akan tetap gagal kena constraint kalau baris
        // "sampah" itu tidak dibuang permanen dulu. Aman di-forceDelete(): semua
        // tabel anak (spatial_features/layer_styles/layer_imports/layer_metadata)
        // ON DELETE CASCADE, dan Layer yang masih ada Data Spasial-nya tidak
        // pernah bisa disoft-delete sejak awal (SpatialLayerController::destroy()).
        SpatialLayer::onlyTrashed()
            ->where(function ($query) use ($category) {
                $query->where('category_id', $category->id)
                    ->orWhere('category_node_id', $category->id);
            })
            ->forceDelete();

        try {
            $category->delete();

            Log::info('Category deleted successfully', ['id' => $id]);

            return redirect()->route('categories.index')
                ->with('success', 'Kategori berhasil dihapus');
        } catch (\Exception $e) {
            Log::error('Error deleting category: '.$e->getMessage());

            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus kategori: '.$e->getMessage());
        }
    }

    /**
     * API method untuk opsi select kaskade (3 level) — satu-satunya endpoint
     * Category API yang benar-benar dikonsumsi JS (lihat categories/index.blade.php).
     * Sejak kategori tidak lagi punya `type`, opsinya mencakup SELURUH pohon
     * kategori (tidak difilter lagi).
     */
    public function getOptions()
    {
        $categories = Category::query()
            ->with(['children.children'])
            ->orderBy('sort_order')
            ->orderBy('nama')
            ->get();

        $options = [];

        foreach ($categories->where('parent_id', null) as $parent) {
            $options[] = ['id' => $parent->id, 'nama' => $parent->nama, 'level' => 0, 'can_have_children' => true];

            foreach ($parent->children as $child) {
                $options[] = ['id' => $child->id, 'nama' => '-- '.$child->nama, 'level' => 1, 'can_have_children' => true];

                foreach ($child->children as $grandchild) {
                    $options[] = ['id' => $grandchild->id, 'nama' => '---- '.$grandchild->nama, 'level' => 2, 'can_have_children' => false];
                }
            }
        }

        return response()->json(['success' => true, 'data' => $options]);
    }

    private function failed(Request $request, array $errors)
    {
        if ($request->ajax()) {
            return response()->json(['success' => false, 'errors' => $errors], 422);
        }

        return redirect()->back()->withErrors($errors)->withInput();
    }
}
