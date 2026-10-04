<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MapType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Hanya method yang benar-benar diroutekan & dikonsumsi UI yang dipertahankan
 * di sini (index/store/update/destroy/getOptions) — lihat routes/backend.php.
 * Method lama (create/edit/show tanpa view, getByType/getTree/toggleActive/
 * getActiveByType/getStatistics/bulkUpdate/duplicate/move/export/import) sudah
 * tidak diroutekan atau tidak dipanggil JS mana pun, jadi dibuang saat rewrite
 * ke skema v3 (plan mellow-weaving-eclipse, Fase 3 lanjutan) alih-alih ditulis
 * ulang untuk struktur dua-tabel (categories_v3/category_nodes) tanpa manfaat.
 */
class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type');

        // Daftar tipe yang diperbolehkan — sumber kebenaran sekarang map_types (bisa
        // bertambah lewat CRUD admin tanpa deploy kode), bukan array literal.
        $validTypes = MapType::active()->pluck('slug')->all();

        if ($type && ! in_array($type, $validTypes)) {
            return redirect()->back();
        }

        // "data_spatial_count" di sini bukan relasi ke tabel data_spatial
        // lama (kategori baru tidak pernah py FK ke sana) — melainkan jumlah
        // fitur v3 (layers.feature_count) milik Layer yang terhubung ke
        // kategori ini, lewat category_id (root) atau category_node_id (turunan).
        $query = Category::query()
            ->selectRaw(<<<'SQL'
                categories_tree_v3.*,
                (
                    SELECT COALESCE(SUM(feature_count), 0) FROM layers
                    WHERE layers.category_id = categories_tree_v3.id
                       OR layers.category_node_id = categories_tree_v3.id
                ) AS data_spatial_count
            SQL)
            ->with(['children.children']); // Load up to 3 levels

        if ($type) {
            $query->where('type', $type);
        }

        $categories = $query->orderBy('parent_id', 'asc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('nama', 'asc')
            ->get();

        $typeLabels = [
            'tematik' => 'Peta Tematik',
        ];

        $typeLabel = $type ? ($typeLabels[$type] ?? '') : '';

        return view('backend.pages.categories.index', compact(
            'categories',
            'type',
            'typeLabel'
        ));
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

    private function validateMaxActiveCategories(string $type, ?string $excludeId = null): bool
    {
        $query = Category::where('type', $type)->where('is_active', true);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->count() < 10;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|exists:map_types,slug',
            'nama' => 'required|string|max:255',
            'warna' => 'nullable|string|max:25',
            'icon' => 'nullable|string|max:255',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'is_marker' => 'boolean',
            'is_active' => 'boolean',
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories_tree_v3,id',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'type.required' => 'Tipe kategori harus dipilih',
            'type.exists' => 'Tipe kategori tidak valid',
            'nama.required' => 'Nama kategori harus diisi',
            'nama.max' => 'Nama kategori maksimal 255 karakter',
            'gambar.image' => 'File harus berupa gambar',
            'gambar.mimes' => 'Format gambar yang diperbolehkan: jpeg, png, jpg, gif, svg, webp',
            'gambar.max' => 'Ukuran gambar maksimal 2MB',
            'parent_id.exists' => 'Kategori induk tidak ditemukan',
        ]);

        if ($validator->fails()) {
            return $this->failed($request, $validator->errors()->toArray());
        }

        if ($request->boolean('is_active') && ! $this->validateMaxActiveCategories($request->type)) {
            return $this->failed($request, ['is_active' => ['Maksimal hanya 10 kategori yang dapat diaktifkan per tipe']]);
        }

        if ($request->parent_id) {
            $parent = Category::find($request->parent_id);

            if ($parent->type !== $request->type) {
                return $this->failed($request, ['parent_id' => ['Kategori induk harus memiliki tipe yang sama']]);
            }

            if (! $this->validateParentHierarchy($request->parent_id)) {
                return $this->failed($request, ['parent_id' => ['Kategori ini tidak dapat dijadikan parent. Maksimal 3 level hirarki (Parent → Child → Grandchild).']]);
            }
        }

        $gambarPath = null;

        try {
            if ($request->hasFile('gambar')) {
                $gambarPath = $request->file('gambar')->store('categories', 'public');
            }

            $category = Category::create([
                'type' => $request->type,
                'user_id' => Auth::id(),
                'nama' => $request->nama,
                'warna' => $request->warna,
                'icon' => $request->icon,
                'gambar' => $gambarPath,
                'is_marker' => $request->boolean('is_marker'),
                'is_active' => $request->boolean('is_active'),
                'deskripsi' => $request->deskripsi,
                'parent_id' => $request->parent_id,
                'sort_order' => $request->input('sort_order', 0),
            ]);

            Log::info('Category created successfully', [
                'id' => $category->id,
                'type' => $category->type,
                'nama' => $category->nama,
                'parent_id' => $category->parent_id,
            ]);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Kategori berhasil dibuat', 'data' => $category]);
            }

            return redirect()->route('categories.index', ['type' => $category->type])
                ->with('success', 'Kategori berhasil dibuat');
        } catch (\Exception $e) {
            if ($gambarPath) {
                Storage::disk('public')->delete($gambarPath);
            }
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
            'type' => 'required|exists:map_types,slug',
            'nama' => 'required|string|max:255',
            'warna' => 'nullable|string|max:25',
            'icon' => 'nullable|string|max:255',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'is_marker' => 'boolean',
            'is_active' => 'boolean',
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories_tree_v3,id',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'type.required' => 'Tipe kategori harus dipilih',
            'type.exists' => 'Tipe kategori tidak valid',
            'nama.required' => 'Nama kategori harus diisi',
            'nama.max' => 'Nama kategori maksimal 255 karakter',
            'gambar.image' => 'File harus berupa gambar',
            'gambar.mimes' => 'Format gambar yang diperbolehkan: jpeg, png, jpg, gif, svg, webp',
            'gambar.max' => 'Ukuran gambar maksimal 2MB',
            'parent_id.exists' => 'Kategori induk tidak ditemukan',
        ]);

        if ($validator->fails()) {
            return $this->failed($request, $validator->errors()->toArray());
        }

        if ($request->boolean('is_active') && ! $this->validateMaxActiveCategories($request->type, $category->id)) {
            return $this->failed($request, ['is_active' => ['Maksimal hanya 10 kategori yang dapat diaktifkan per tipe']]);
        }

        if ($request->parent_id) {
            $parent = Category::find($request->parent_id);

            if ($parent->type !== $request->type) {
                return $this->failed($request, ['parent_id' => ['Kategori induk harus memiliki tipe yang sama']]);
            }

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
            $updateData = [
                'type' => $request->type,
                'nama' => $request->nama,
                'warna' => $request->warna,
                'icon' => $request->icon,
                'is_marker' => $request->boolean('is_marker'),
                'is_active' => $request->boolean('is_active'),
                'deskripsi' => $request->deskripsi,
                'parent_id' => $request->parent_id,
                'sort_order' => $request->filled('sort_order') ? $request->input('sort_order') : $category->sort_order,
            ];

            if ($request->hasFile('gambar')) {
                if ($category->gambar) {
                    Storage::disk('public')->delete($category->gambar);
                }
                $updateData['gambar'] = $request->file('gambar')->store('categories', 'public');
            } elseif ($request->boolean('remove_gambar')) {
                if ($category->gambar) {
                    Storage::disk('public')->delete($category->gambar);
                }
                $updateData['gambar'] = null;
            }

            $category->update($updateData);

            Log::info('Category updated successfully', [
                'id' => $category->id,
                'type' => $category->type,
                'nama' => $category->nama,
                'parent_id' => $category->parent_id,
            ]);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Kategori berhasil diperbarui', 'data' => $category]);
            }

            return redirect()->route('categories.index', ['type' => $category->type])
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
        $names = DB::table('layers')
            ->where('category_id', $category->id)
            ->orWhere('category_node_id', $category->id)
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

        try {
            $categoryType = $category->type;

            if ($category->gambar) {
                Storage::disk('public')->delete($category->gambar);
            }

            $category->delete();

            Log::info('Category deleted successfully', ['id' => $id, 'type' => $categoryType]);

            return redirect()->route('categories.index', ['type' => $categoryType])
                ->with('success', 'Kategori berhasil dihapus');
        } catch (\Exception $e) {
            Log::error('Error deleting category: '.$e->getMessage());

            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus kategori: '.$e->getMessage());
        }
    }

    /**
     * API method untuk opsi select kaskade (3 level) — satu-satunya endpoint
     * Category API yang benar-benar dikonsumsi JS (lihat categories/index.blade.php).
     */
    public function getOptions(string $type)
    {
        $categories = Category::where('type', $type)
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
