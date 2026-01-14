<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type');

        // Daftar tipe yang diperbolehkan
        $validTypes = ['tematik', 'psd', 'psn', 'pokir_dprd', 'usulan_musrenbang'];

        // Cek jika type ada dan tidak valid
        if ($type && !in_array($type, $validTypes)) {
            return redirect()->back();
        }

        // Query utama dengan nested loading
        $query = Category::withCount('dataSpatial')
            ->with(['children.children']); // Load up to 3 levels

        if ($type) {
            $query->where('type', $type);
        }

        $categories = $query->orderBy('parent_id', 'asc')
            ->orderBy('nama', 'asc')
            ->get();

        // Label yang akan ditampilkan
        $typeLabels = [
            'tematik' => 'Peta Tematik',
            'psd' => 'PSD (Proyek Strategis Daerah)',
            'psn' => 'PSN (Proyek Strategis Nasional)',
            'pokir_dprd' => 'Pokir DPRD',
            'usulan_musrenbang' => 'Usulan Musrenbang'
        ];

        $typeLabel = $type ? ($typeLabels[$type] ?? '') : '';

        return view('backend.pages.categories.index', compact(
            'categories',
            'type',
            'typeLabel'
        ));
    }

    /**
     * Get category depth level (0 = root, 1 = child, 2 = grandchild)
     */
    private function getCategoryDepth($category)
    {
        $depth = 0;
        $current = $category;

        while ($current->parent_id !== null) {
            $depth++;
            $current = Category::find($current->parent_id);
            if (!$current || $depth > 3) break; // Prevent infinite loop
        }

        return $depth;
    }

    /**
     * Validasi parent untuk hirarki 3 level
     */
    private function validateParentHierarchy($parentId, $currentCategoryId = null)
    {
        if (!$parentId) return true;

        $parent = Category::find($parentId);
        if (!$parent) return false;

        // Hitung depth dari parent
        $parentDepth = $this->getCategoryDepth($parent);

        // Parent tidak boleh lebih dari level 1 (karena akan jadi level 2, dan anaknya jadi level 3)
        if ($parentDepth >= 2) {
            return false;
        }

        // Cek circular reference jika editing
        if ($currentCategoryId) {
            $childrenIds = [];
            $this->getChildrenIds(Category::find($currentCategoryId), $childrenIds);
            if (in_array($parentId, $childrenIds)) {
                return false;
            }
        }

        return true;
    }


    public function create(Request $request)
    {
        $type = $request->get('type');
        $parentId = $request->get('parent_id');

        // Get available types
        $types = [
            'tematik' => 'Peta Tematik',
            'musrenbang' => 'Usulan Musrenbang',
            'pokir' => 'POKIR DPRD',
            'psd' => 'Proyek Strategis Daerah',
            'psn' => 'Proyek Strategis Nasional'
        ];

        // Get potential parents if type is selected
        $potentialParents = [];
        if ($type) {
            $potentialParents = Category::where('type', $type)
                ->whereNull('parent_id') // Only root categories can be parents
                ->orderBy('name')
                ->get();
        }

        return view('backend.pages.categories.create', compact('types', 'type', 'parentId', 'potentialParents'));
    }

    // /**
    //  * Validasi maksimal 10 kategori aktif per tipe
    //  */
    private function validateMaxActiveCategories($type, $excludeId = null)
    {
        $query = Category::where('type', $type)->where('is_active', true);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $activeCount = $query->count();

        return $activeCount < 10;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:tematik,usulan_musrenbang,pokir_dprd,psd,psn',
            'nama' => 'required|string|max:255',
            'warna' => 'nullable|string|max:25',
            'icon' => 'nullable|string|max:255',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'is_marker' => 'boolean',
            'is_active' => 'boolean',
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id'
        ], [
            'type.required' => 'Tipe kategori harus dipilih',
            'type.in' => 'Tipe kategori tidak valid',
            'nama.required' => 'Nama kategori harus diisi',
            'nama.max' => 'Nama kategori maksimal 255 karakter',
            'gambar.image' => 'File harus berupa gambar',
            'gambar.mimes' => 'Format gambar yang diperbolehkan: jpeg, png, jpg, gif, svg, webp',
            'gambar.max' => 'Ukuran gambar maksimal 2MB',
            'parent_id.exists' => 'Kategori induk tidak ditemukan',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Validasi maksimal 10 kategori aktif jika is_active = true
        if ($request->boolean('is_active')) {
            if (!$this->validateMaxActiveCategories($request->type)) {
                $error = ['is_active' => ['Maksimal hanya 10 kategori yang dapat diaktifkan per tipe']];
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }
                return redirect()->back()->withErrors($error)->withInput();
            }
        }

        // Validasi parent_id jika ada
        if ($request->parent_id) {
            $parent = Category::find($request->parent_id);

            // 1. Parent harus memiliki type yang sama
            if ($parent->type !== $request->type) {
                $error = ['parent_id' => ['Kategori induk harus memiliki tipe yang sama']];
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }
                return redirect()->back()->withErrors($error)->withInput();
            }

            // 2. Validasi hirarki 3 level
            if (!$this->validateParentHierarchy($request->parent_id)) {
                $error = ['parent_id' => ['Kategori ini tidak dapat dijadikan parent. Maksimal 3 level hirarki (Parent → Child → Grandchild).']];
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }
                return redirect()->back()->withErrors($error)->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $gambarPath = null;
            if ($request->hasFile('gambar')) {
                $gambarPath = $request->file('gambar')->store('categories', 'public');
            }

            $category = Category::create([
                'type' => $request->type,
                'user_id' => Auth::user()->id,
                'nama' => $request->nama,
                'warna' => $request->warna,
                'icon' => $request->icon,
                'gambar' => $gambarPath,
                'is_marker' => $request->boolean('is_marker'),
                'is_active' => $request->boolean('is_active'),
                'deskripsi' => $request->deskripsi,
                'parent_id' => $request->parent_id
            ]);

            DB::commit();

            Log::info('Category created successfully', [
                'id' => $category->id,
                'type' => $category->type,
                'nama' => $category->nama,
                'parent_id' => $category->parent_id,
                'depth' => $this->getCategoryDepth($category),
                'is_active' => $category->is_active
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kategori berhasil dibuat',
                    'data' => $category
                ]);
            }

            return redirect()->route('categories.index', ['type' => $category->type])
                ->with('success', 'Kategori berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollback();
            if (isset($gambarPath) && $gambarPath) {
                Storage::disk('public')->delete($gambarPath);
            }
            Log::error('Error creating category: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat membuat kategori: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat membuat kategori: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show($id)
    {
        $category = Category::with(['parent', 'children', 'dataSpatial'])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $category
            ]);
        }

        return view('backend.pages.categories.show', compact('category'));
    }

    public function edit($id)
    {
        $category = Category::with(['parent', 'children'])->findOrFail($id);

        $types = [
            'peta_tematik' => 'Peta Tematik (Lokasi)',
            'musrenbang' => 'Usulan Musrenbang',
            'pokir' => 'POKIR DPRD',
            'psd' => 'Proyek Strategis Daerah',
            'psn' => 'Proyek Strategis Nasional'
        ];

        // Get potential parents (exclude self and children to prevent circular reference)
        $excludeIds = [$category->id];
        $this->getChildrenIds($category, $excludeIds);

        $potentialParents = Category::where('type', $category->type)
            ->whereNotIn('id', $excludeIds)
            ->whereNull('parent_id') // Only root categories can be parents
            ->orderBy('name')
            ->get();

        return view('backend.pages.categories.edit', compact('category', 'types', 'potentialParents'));
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $user = Auth::user();
        $userRole = $user->role->slug ?? null;

        // Cek otorisasi
        if (!in_array($userRole, ['super-admin', 'admin-bappeda']) && $category->user_id !== $user->id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mengedit kategori ini.');
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:tematik,usulan_musrenbang,pokir_dprd,psd,psn',
            'nama' => 'required|string|max:255',
            'warna' => 'nullable|string|max:25',
            'icon' => 'nullable|string|max:255',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'is_marker' => 'boolean',
            'is_active' => 'boolean',
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id'
        ], [
            'type.required' => 'Tipe kategori harus dipilih',
            'type.in' => 'Tipe kategori tidak valid',
            'nama.required' => 'Nama kategori harus diisi',
            'nama.max' => 'Nama kategori maksimal 255 karakter',
            'gambar.image' => 'File harus berupa gambar',
            'gambar.mimes' => 'Format gambar yang diperbolehkan: jpeg, png, jpg, gif, svg, webp',
            'gambar.max' => 'Ukuran gambar maksimal 2MB',
            'parent_id.exists' => 'Kategori induk tidak ditemukan',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Validasi maksimal 10 kategori aktif
        if ($request->boolean('is_active')) {
            if (!$this->validateMaxActiveCategories($request->type, $category->id)) {
                $error = ['is_active' => ['Maksimal hanya 10 kategori yang dapat diaktifkan per tipe']];
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }
                return redirect()->back()->withErrors($error)->withInput();
            }
        }

        // Validasi parent_id jika ada
        if ($request->parent_id) {
            $parent = Category::find($request->parent_id);

            // 1. Parent harus memiliki type yang sama
            if ($parent->type !== $request->type) {
                $error = ['parent_id' => ['Kategori induk harus memiliki tipe yang sama']];
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }
                return redirect()->back()->withErrors($error)->withInput();
            }

            // 2. Prevent circular reference
            if ($request->parent_id == $category->id) {
                $error = ['parent_id' => ['Kategori tidak boleh menjadi induk dari dirinya sendiri']];
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }
                return redirect()->back()->withErrors($error)->withInput();
            }

            // 3. Check if parent is a child of current category
            $childrenIds = [];
            $this->getChildrenIds($category, $childrenIds);
            if (in_array($request->parent_id, $childrenIds)) {
                $error = ['parent_id' => ['Kategori induk tidak boleh merupakan anak dari kategori ini']];
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }
                return redirect()->back()->withErrors($error)->withInput();
            }

            // 4. Validasi hirarki 3 level untuk update
            if (!$this->validateParentHierarchy($request->parent_id, $category->id)) {
                $error = ['parent_id' => ['Kategori ini tidak dapat dijadikan parent. Maksimal 3 level hirarki (Parent → Child → Grandchild).']];
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }
                return redirect()->back()->withErrors($error)->withInput();
            }

            // 5. Jika kategori ini sudah punya anak level 2, tidak boleh dijadikan child level 2
            $hasGrandchildren = $this->hasGrandchildren($category);
            $parentDepth = $this->getCategoryDepth($parent);

            if ($hasGrandchildren && $parentDepth >= 1) {
                $error = ['parent_id' => ['Kategori ini memiliki sub-kategori level 3. Tidak dapat dipindahkan ke level yang lebih dalam.']];
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'errors' => $error], 422);
                }
                return redirect()->back()->withErrors($error)->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $updateData = [
                'type' => $request->type,
                'nama' => $request->nama,
                'warna' => $request->warna,
                'icon' => $request->icon,
                'is_marker' => $request->boolean('is_marker'),
                'is_active' => $request->boolean('is_active'),
                'deskripsi' => $request->deskripsi,
                'parent_id' => $request->parent_id
            ];

            // Handle gambar upload
            if ($request->hasFile('gambar')) {
                if ($category->gambar) {
                    Storage::disk('public')->delete($category->gambar);
                }
                $updateData['gambar'] = $request->file('gambar')->store('categories', 'public');
            } elseif ($request->has('remove_gambar') && $request->remove_gambar) {
                if ($category->gambar) {
                    Storage::disk('public')->delete($category->gambar);
                }
                $updateData['gambar'] = null;
            }

            $category->update($updateData);
            DB::commit();

            Log::info('Category updated successfully', [
                'id' => $category->id,
                'type' => $category->type,
                'nama' => $category->nama,
                'parent_id' => $category->parent_id,
                'depth' => $this->getCategoryDepth($category),
                'is_active' => $category->is_active
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kategori berhasil diperbarui',
                    'data' => $category
                ]);
            }

            return redirect()->route('categories.index', ['type' => $category->type])
                ->with('success', 'Kategori berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating category: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memperbarui kategori: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat memperbarui kategori: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Check if category has grandchildren (level 3)
     */
    private function hasGrandchildren($category)
    {
        $children = Category::where('parent_id', $category->id)->get();
        foreach ($children as $child) {
            if (Category::where('parent_id', $child->id)->exists()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper method untuk mendapatkan semua children IDs recursively
     */
    private function getChildrenIds($category, &$childrenIds)
    {
        $children = Category::where('parent_id', $category->id)->get();
        foreach ($children as $child) {
            $childrenIds[] = $child->id;
            $this->getChildrenIds($child, $childrenIds);
        }
    }

    /**
     * Validasi maksimal 10 kategori aktif per tipe
     */
    // private function validateMaxActiveCategories($type, $excludeId = null)
    // {
    //     $query = Category::where('type', $type)->where('is_active', true);
    //     if ($excludeId) {
    //         $query->where('id', '!=', $excludeId);
    //     }
    //     $activeCount = $query->count();
    //     return $activeCount < 10;
    // }

    public function destroy($id)
    {
        $category = Category::with(['children', 'dataSpatial'])->findOrFail($id);
        $user = Auth::user();
        $userRole = $user->role->slug ?? null;

        // Cek otorisasi: hanya super-admin, admin-bappeda, atau pembuat
        if (!in_array($userRole, ['super-admin', 'admin-bappeda']) && $category->user_id !== $user->id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menghapus kategori ini.');
        }

        // Check if category has children
        if ($category->children->count() > 0) {
            return redirect()->back()
                ->with('error', 'Kategori tidak dapat dihapus karena masih memiliki sub-kategori');
        }

        // Check if category is used by data spatial
        if ($category->dataSpatial->count() > 0) {
            return redirect()->back()
                ->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh data spatial');
        }

        try {
            $categoryType = $category->type;

            // Delete gambar file if exists
            if ($category->gambar) {
                Storage::disk('public')->delete($category->gambar);
            }

            $category->delete();

            Log::info('Category deleted successfully', [
                'id' => $id,
                'type' => $categoryType
            ]);

            return redirect()->route('categories.index', ['type' => $categoryType])
                ->with('success', 'Kategori berhasil dihapus');
        } catch (\Exception $e) {
            Log::error('Error deleting category: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menghapus kategori: ' . $e->getMessage());
        }
    }

    /**
     * Toggle status aktif kategori
     */
    public function toggleActive($id)
    {
        $category = Category::findOrFail($id);
        $user = Auth::user();
        $userRole = $user->role->slug ?? null;

        // Cek otorisasi
        if (!in_array($userRole, ['super-admin', 'admin-bappeda']) && $category->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk mengubah status kategori ini.'
            ], 403);
        }

        try {
            // Jika ingin mengaktifkan, cek batas maksimal
            if (!$category->is_active) {
                if (!$this->validateMaxActiveCategories($category->type, $category->id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Maksimal hanya 10 kategori yang dapat diaktifkan per tipe'
                    ], 422);
                }
            }

            $category->is_active = !$category->is_active;
            $category->save();

            $status = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

            Log::info('Category status toggled', [
                'id' => $category->id,
                'nama' => $category->nama,
                'is_active' => $category->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => "Kategori berhasil {$status}",
                'is_active' => $category->is_active
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling category status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah status kategori'
            ], 500);
        }
    }

    /**
     * Get active categories by type
     */
    public function getActiveByType($type)
    {
        $categories = Category::where('type', $type)
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * API method to get categories by type
     */
    public function getByType($type)
    {
        $categories = Category::where('type', $type)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * API method to get category tree
     */
    public function getTree($type = null)
    {
        $query = Category::with('children');

        if ($type) {
            $query->where('type', $type);
        }

        $categories = $query->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }


    /**
     * API method to get category options for select (updated for 3-level)
     */
    public function getOptions($type)
    {
        $categories = Category::where('type', $type)
            ->with(['children.children'])
            ->orderBy('nama')
            ->get();

        // Build hierarchical options
        $options = [];

        foreach ($categories->where('parent_id', null) as $parent) {
            $options[] = [
                'id' => $parent->id,
                'nama' => $parent->nama,
                'level' => 0,
                'can_have_children' => true
            ];

            foreach ($parent->children as $child) {
                $options[] = [
                    'id' => $child->id,
                    'nama' => '-- ' . $child->nama,
                    'level' => 1,
                    'can_have_children' => true
                ];

                foreach ($child->children as $grandchild) {
                    $options[] = [
                        'id' => $grandchild->id,
                        'nama' => '---- ' . $grandchild->nama,
                        'level' => 2,
                        'can_have_children' => false
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $options
        ]);
    }

    private function buildTree($categories)
    {
        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'nama' => $category->nama,
                'type' => $category->type,
                'warna' => $category->warna,
                'icon' => $category->icon,
                'gambar' => $category->gambar,
                'is_marker' => $category->is_marker,
                'is_active' => $category->is_active,
                'deskripsi' => $category->deskripsi,
                'parent_id' => $category->parent_id,
                'children' => $this->buildTree($category->children)
            ];
        });
    }

    // === UTILITY METHODS ===

    public function getStatistics()
    {
        $stats = [
            'total_categories' => Category::count(),
            'active_categories' => Category::where('is_active', true)->count(),
            'by_type' => Category::select(
                'type',
                DB::raw('count(*) as total'),
                DB::raw('sum(case when is_active = 1 then 1 else 0 end) as active')
            )->groupBy('type')->get(),
            'root_categories' => Category::roots()->count(),
            'child_categories' => Category::whereNotNull('parent_id')->count(),
            'marker_categories' => Category::markers()->count(),
            'categories_with_images' => Category::whereNotNull('gambar')->count()
        ];

        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'action' => 'required|in:delete,update_type,toggle_marker,toggle_active'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $categoryIds = collect($request->categories)->pluck('id');
            $updatedCount = 0;

            switch ($request->action) {
                case 'delete':
                    // Check if any category has children or is used by data spatial
                    $categoriesWithChildren = Category::whereIn('id', $categoryIds)
                        ->has('children')
                        ->count();

                    $categoriesWithData = Category::whereIn('id', $categoryIds)
                        ->has('dataSpatial')
                        ->count();

                    if ($categoriesWithChildren > 0 || $categoriesWithData > 0) {
                        throw new \Exception('Beberapa kategori tidak dapat dihapus karena masih memiliki sub-kategori atau digunakan oleh data spatial');
                    }

                    // Delete associated images
                    $categories = Category::whereIn('id', $categoryIds)->get();
                    foreach ($categories as $category) {
                        if ($category->gambar) {
                            Storage::disk('public')->delete($category->gambar);
                        }
                    }

                    $updatedCount = Category::whereIn('id', $categoryIds)->delete();
                    break;

                case 'update_type':
                    if (!$request->has('new_type')) {
                        throw new \Exception('Tipe baru harus disediakan');
                    }

                    $updatedCount = Category::whereIn('id', $categoryIds)
                        ->update(['type' => $request->new_type]);
                    break;

                case 'toggle_marker':
                    // Toggle is_marker for each category
                    foreach ($categoryIds as $id) {
                        $category = Category::find($id);
                        $category->is_marker = !$category->is_marker;
                        $category->save();
                        $updatedCount++;
                    }
                    break;

                case 'toggle_active':
                    // Toggle is_active for each category dengan validasi batas maksimal
                    foreach ($categoryIds as $id) {
                        $category = Category::find($id);

                        // Jika ingin mengaktifkan, cek batas maksimal
                        if (!$category->is_active) {
                            if (!$this->validateMaxActiveCategories($category->type, $category->id)) {
                                throw new \Exception("Maksimal 10 kategori aktif untuk tipe {$category->type}. Kategori '{$category->nama}' tidak dapat diaktifkan.");
                            }
                        }

                        $category->is_active = !$category->is_active;
                        $category->save();
                        $updatedCount++;
                    }
                    break;
            }

            DB::commit();

            Log::info('Bulk category update completed', [
                'action' => $request->action,
                'count' => $updatedCount,
                'category_ids' => $categoryIds->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil {$request->action} {$updatedCount} kategori",
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error in bulk category update: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function duplicate(Request $request, $id)
    {
        $originalCategory = Category::with('children')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'type' => 'nullable|in:tematik,usulan_musrenbang,pokir_dprd,psd,psn',
            'include_children' => 'boolean',
            'copy_image' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $newGambarPath = null;
            // Copy gambar if requested and exists
            if ($request->boolean('copy_image') && $originalCategory->gambar) {
                if (Storage::disk('public')->exists($originalCategory->gambar)) {
                    $extension = pathinfo($originalCategory->gambar, PATHINFO_EXTENSION);
                    $newGambarPath = 'categories/' . uniqid() . '.' . $extension;
                    Storage::disk('public')->copy($originalCategory->gambar, $newGambarPath);
                }
            }

            // Create duplicate of main category
            $newCategory = Category::create([
                'type' => $request->type ?? $originalCategory->type,
                'user_id' => Auth::user()->id,
                'nama' => $request->nama,
                'warna' => $originalCategory->warna,
                'icon' => $originalCategory->icon,
                'gambar' => $newGambarPath,
                'is_marker' => $originalCategory->is_marker,
                'is_active' => false, // Duplicate dimulai sebagai tidak aktif
                'deskripsi' => $originalCategory->deskripsi,
                'parent_id' => $originalCategory->parent_id
            ]);

            // Duplicate children if requested
            if ($request->boolean('include_children')) {
                $this->duplicateChildren($originalCategory, $newCategory, $request->boolean('copy_image'));
            }

            DB::commit();

            Log::info('Category duplicated successfully', [
                'original_id' => $originalCategory->id,
                'new_id' => $newCategory->id,
                'include_children' => $request->boolean('include_children'),
                'copy_image' => $request->boolean('copy_image')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil diduplikasi',
                'category' => $newCategory->load('children')
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            // Delete copied gambar if exists
            if (isset($newGambarPath) && $newGambarPath) {
                Storage::disk('public')->delete($newGambarPath);
            }

            Log::error('Error duplicating category: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menduplikasi kategori: ' . $e->getMessage()
            ], 500);
        }
    }

    private function duplicateChildren($originalCategory, $newParent, $copyImages = false)
    {
        foreach ($originalCategory->children as $child) {
            $childGambarPath = null;

            // Copy child gambar if requested and exists
            if ($copyImages && $child->gambar) {
                if (Storage::disk('public')->exists($child->gambar)) {
                    $extension = pathinfo($child->gambar, PATHINFO_EXTENSION);
                    $childGambarPath = 'categories/' . uniqid() . '.' . $extension;
                    Storage::disk('public')->copy($child->gambar, $childGambarPath);
                }
            }

            $newChild = Category::create([
                'type' => $newParent->type,
                'user_id' => Auth::user()->id,
                'nama' => $child->nama,
                'warna' => $child->warna,
                'icon' => $child->icon,
                'gambar' => $childGambarPath,
                'is_marker' => $child->is_marker,
                'is_active' => false, // Children juga dimulai sebagai tidak aktif
                'deskripsi' => $child->deskripsi,
                'parent_id' => $newParent->id
            ]);

            // Recursively duplicate grandchildren
            if ($child->children->count() > 0) {
                $this->duplicateChildren($child, $newChild, $copyImages);
            }
        }
    }

    public function move(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'new_parent_id' => 'nullable|exists:categories,id',
            'new_type' => 'nullable|in:tematik,usulan_musrenbang,pokir_dprd,psd,psn'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Validate new parent
            if ($request->new_parent_id) {
                $newParent = Category::find($request->new_parent_id);

                // Check type compatibility
                $targetType = $request->new_type ?? $category->type;
                if ($newParent->type !== $targetType) {
                    throw new \Exception('Kategori induk harus memiliki tipe yang sama');
                }

                // Prevent circular reference
                if ($request->new_parent_id == $category->id) {
                    throw new \Exception('Kategori tidak boleh menjadi induk dari dirinya sendiri');
                }

                // Check if new parent is a child of current category
                $childrenIds = [];
                $this->getChildrenIds($category, $childrenIds);
                if (in_array($request->new_parent_id, $childrenIds)) {
                    throw new \Exception('Kategori induk tidak boleh merupakan anak dari kategori ini');
                }
            }

            DB::beginTransaction();

            $updateData = [];
            if ($request->has('new_parent_id')) {
                $updateData['parent_id'] = $request->new_parent_id;
            }
            if ($request->has('new_type')) {
                $updateData['type'] = $request->new_type;

                // Update children type as well
                $this->updateChildrenType($category, $request->new_type);
            }

            $category->update($updateData);

            DB::commit();

            Log::info('Category moved successfully', [
                'category_id' => $category->id,
                'new_parent_id' => $request->new_parent_id,
                'new_type' => $request->new_type
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil dipindahkan',
                'category' => $category->fresh(['parent', 'children'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error moving category: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memindahkan kategori: ' . $e->getMessage()
            ], 500);
        }
    }

    private function updateChildrenType($category, $newType)
    {
        foreach ($category->children as $child) {
            $child->update(['type' => $newType]);
            $this->updateChildrenType($child, $newType);
        }
    }

    public function export(Request $request)
    {
        $type = $request->get('type');
        $format = $request->get('format', 'json');

        $query = Category::with(['parent', 'children']);

        if ($type) {
            $query->where('type', $type);
        }

        $categories = $query->orderBy('type')->orderBy('nama')->get();

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($categories, $type);
            case 'excel':
                return $this->exportToExcel($categories, $type);
            case 'json':
            default:
                return response()->json([
                    'success' => true,
                    'type' => $type,
                    'categories' => $categories,
                    'exported_at' => now()->toISOString()
                ]);
        }
    }

    private function exportToCsv($categories, $type)
    {
        $filename = 'categories' . ($type ? "_$type" : '') . '_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($categories) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'ID',
                'Type',
                'Nama',
                'Warna',
                'Icon',
                'Gambar',
                'Is Marker',
                'Is Active',
                'Deskripsi',
                'Parent ID',
                'Parent Nama',
                'Created At',
                'Updated At'
            ]);

            foreach ($categories as $category) {
                fputcsv($file, [
                    $category->id,
                    $category->type,
                    $category->nama,
                    $category->warna,
                    $category->icon,
                    $category->gambar,
                    $category->is_marker ? 'Yes' : 'No',
                    $category->is_active ? 'Yes' : 'No',
                    $category->deskripsi,
                    $category->parent_id,
                    $category->parent?->nama,
                    $category->created_at,
                    $category->updated_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToExcel($categories, $type)
    {
        // Implementation for Excel export would require a library like PhpSpreadsheet
        // For now, return JSON with note
        return response()->json([
            'success' => false,
            'message' => 'Excel export belum diimplementasi. Gunakan format CSV atau JSON.',
            'available_formats' => ['json', 'csv']
        ], 501);
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:json,csv',
            'type' => 'required|in:tematik,usulan_musrenbang,pokir_dprd,psd,psn',
            'overwrite' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();

            $importedCount = 0;

            DB::beginTransaction();

            if ($extension === 'json') {
                $importedCount = $this->importFromJson($file, $request->type, $request->boolean('overwrite'));
            } elseif ($extension === 'csv') {
                $importedCount = $this->importFromCsv($file, $request->type, $request->boolean('overwrite'));
            }

            DB::commit();

            Log::info('Categories imported successfully', [
                'file' => $file->getClientOriginalName(),
                'type' => $request->type,
                'count' => $importedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengimpor {$importedCount} kategori",
                'imported_count' => $importedCount
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error importing categories: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengimpor: ' . $e->getMessage()
            ], 500);
        }
    }

    private function importFromJson($file, $type, $overwrite)
    {
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);

        if (!$data || !is_array($data)) {
            throw new \Exception('Format JSON tidak valid');
        }

        $importedCount = 0;

        foreach ($data as $item) {
            if (!isset($item['nama'])) {
                continue;
            }

            $categoryData = [
                'type' => $type,
                'user_id' => Auth::user()->id,
                'nama' => $item['nama'],
                'warna' => $item['warna'] ?? null,
                'icon' => $item['icon'] ?? null,
                'gambar' => null, // Import tidak menyertakan file gambar
                'is_marker' => $item['is_marker'] ?? false,
                'is_active' => false, // Import dimulai sebagai tidak aktif
                'deskripsi' => $item['deskripsi'] ?? null,
                'parent_id' => null // Handle parent relationships in second pass
            ];

            if ($overwrite) {
                Category::updateOrCreate(
                    ['nama' => $item['nama'], 'type' => $type],
                    $categoryData
                );
            } else {
                if (!Category::where('nama', $item['nama'])->where('type', $type)->exists()) {
                    Category::create($categoryData);
                }
            }

            $importedCount++;
        }

        return $importedCount;
    }

    private function importFromCsv($file, $type, $overwrite)
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            throw new \Exception('Tidak dapat membaca file CSV');
        }

        $importedCount = 0;
        $isFirstRow = true;

        while (($row = fgetcsv($handle)) !== false) {
            if ($isFirstRow) {
                $isFirstRow = false;
                continue; // Skip header row
            }

            if (count($row) < 2 || empty($row[1])) {
                continue; // Skip invalid rows
            }

            $categoryData = [
                'type' => $type,
                'user_id' => Auth::user()->id,
                'nama' => $row[1] ?? '',
                'warna' => $row[2] ?? null,
                'icon' => $row[3] ?? null,
                'gambar' => null, // Import tidak menyertakan file gambar
                'is_marker' => ($row[4] ?? '') === 'Yes',
                'is_active' => false, // Import dimulai sebagai tidak aktif
                'deskripsi' => $row[5] ?? null,
                'parent_id' => null
            ];

            if (empty($categoryData['nama'])) {
                continue;
            }

            if ($overwrite) {
                Category::updateOrCreate(
                    ['nama' => $categoryData['nama'], 'type' => $type],
                    $categoryData
                );
            } else {
                if (!Category::where('nama', $categoryData['nama'])->where('type', $type)->exists()) {
                    Category::create($categoryData);
                }
            }

            $importedCount++;
        }

        fclose($handle);
        return $importedCount;
    }
}
