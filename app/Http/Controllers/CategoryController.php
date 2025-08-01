<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index(Request $request)
{
    $type = $request->get('type');

    // Daftar tipe yang diperbolehkan
    $validTypes = ['layers', 'psd', 'psn', 'pokir_dprds', 'musrenbangs'];

    // Cek jika type ada dan tidak valid
    if ($type && !in_array($type, $validTypes)) {
        return redirect()->back(); // langsung stop jika tidak sesuai
    }

    // Ambil semua kategori untuk dropdown parent
    $allCategories = Category::orderBy('nama', 'asc')->get();

    // Query utama
    $query = Category::withCount('dataSpatial');
    if ($type) {
        $query->where('type', $type);
    }

    $categories = $query->orderBy('parent_id', 'asc')
                        ->orderBy('nama', 'asc')
                        ->get();

    // Label yang akan ditampilkan
    $typeLabels = [
        'layers' => 'Layers (Lokasi)',
        'psd' => 'PSD (Proyek Strategis Daerah)',
        'psn' => 'PSN (Proyek Strategis Nasional)',
        'pokir_dprds' => 'Pokir DPRD',
        'musrenbangs' => 'Musrenbang (Usulan Musrenbang)'
    ];

    $typeLabel = $type ? ($typeLabels[$type] ?? '') : '';

    return view('backend.pages.categories.index', compact(
        'categories',
        'allCategories',
        'type',
        'typeLabel'
    ));
}


    public function create(Request $request)
    {
        $type = $request->get('type');
        $parentId = $request->get('parent_id');
        
        // Get available types
        $types = [
            'peta_tematik' => 'Peta Tematik (Lokasi)',
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

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:layers,musrenbangs,pokir_dprds,psd,psn',
            'nama' => 'required|string|max:255',
            'warna' => 'nullable|string|max:25',
            'icon' => 'nullable|string|max:255',
            'is_marker' => 'boolean',
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id'
        ], [
            'type.required' => 'Tipe kategori harus dipilih',
            'type.in' => 'Tipe kategori tidak valid',
            'nama.required' => 'Nama kategori harus diisi',
            'nama.max' => 'Nama kategori maksimal 255 karakter',
            'parent_id.exists' => 'Kategori induk tidak ditemukan',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Validasi parent memiliki type yang sama
        if ($request->parent_id) {
            $parent = Category::find($request->parent_id);
            if ($parent->type !== $request->type) {
                $error = ['parent_id' => ['Kategori induk harus memiliki tipe yang sama']];
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $error
                    ], 422);
                }
                
                return redirect()->back()
                    ->withErrors($error)
                    ->withInput();
            }

            // Validasi parent tidak boleh sudah memiliki parent (maksimal 1 level)
            if ($parent->parent_id !== null) {
                $error = ['parent_id' => ['Kategori yang dipilih sebagai parent sudah merupakan sub-kategori. Maksimal hanya 1 level hierarki.']];
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $error
                    ], 422);
                }
                
                return redirect()->back()
                    ->withErrors($error)
                    ->withInput();
            }

            // Validasi parent tidak boleh sudah memiliki anak (untuk menjaga konsistensi 1 level)
            $hasChildren = Category::where('parent_id', $request->parent_id)->exists();
            if ($hasChildren) {
                $error = ['parent_id' => ['Kategori yang dipilih sudah memiliki sub-kategori. Tidak dapat menambah sub-kategori lagi.']];
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $error
                    ], 422);
                }
                
                return redirect()->back()
                    ->withErrors($error)
                    ->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $category = Category::create([
                'type' => $request->type,
                'nama' => $request->nama,
                'warna' => $request->warna,
                'icon' => $request->icon,
                'is_marker' => $request->boolean('is_marker'),
                'deskripsi' => $request->deskripsi,
                'parent_id' => $request->parent_id
            ]);

            DB::commit();

            Log::info('Category created successfully', [
                'id' => $category->id,
                'type' => $category->type,
                'nama' => $category->nama
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
        
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:layers,musrenbangs,pokir_dprds,psd,psn',
            'nama' => 'required|string|max:255',
            'warna' => 'nullable|string|max:25',
            'icon' => 'nullable|string|max:255',
            'is_marker' => 'boolean',
            'deskripsi' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id'
        ], [
            'type.required' => 'Tipe kategori harus dipilih',
            'type.in' => 'Tipe kategori tidak valid',
            'nama.required' => 'Nama kategori harus diisi',
            'nama.max' => 'Nama kategori maksimal 255 karakter',
            'parent_id.exists' => 'Kategori induk tidak ditemukan',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Validasi parent memiliki type yang sama
        if ($request->parent_id) {
            $parent = Category::find($request->parent_id);
            if ($parent->type !== $request->type) {
                $error = ['parent_id' => ['Kategori induk harus memiliki tipe yang sama']];
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $error
                    ], 422);
                }
                
                return redirect()->back()
                    ->withErrors($error)
                    ->withInput();
            }
            
            // Prevent circular reference
            if ($request->parent_id == $category->id) {
                $error = ['parent_id' => ['Kategori tidak boleh menjadi induk dari dirinya sendiri']];
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $error
                    ], 422);
                }
                
                return redirect()->back()
                    ->withErrors($error)
                    ->withInput();
            }
            
            // Check if parent is a child of current category
            $childrenIds = [];
            $this->getChildrenIds($category, $childrenIds);
            if (in_array($request->parent_id, $childrenIds)) {
                $error = ['parent_id' => ['Kategori induk tidak boleh merupakan anak dari kategori ini']];
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $error
                    ], 422);
                }
                
                return redirect()->back()
                    ->withErrors($error)
                    ->withInput();
            }

            // Validasi parent tidak boleh sudah memiliki parent (maksimal 1 level)
            if ($parent->parent_id !== null) {
                $error = ['parent_id' => ['Kategori yang dipilih sebagai parent sudah merupakan sub-kategori. Maksimal hanya 1 level hierarki.']];
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $error
                    ], 422);
                }
                
                return redirect()->back()
                    ->withErrors($error)
                    ->withInput();
            }

            // Validasi parent tidak boleh sudah memiliki anak (kecuali anak tersebut adalah kategori yang sedang diedit)
            $hasOtherChildren = Category::where('parent_id', $request->parent_id)
                                      ->where('id', '!=', $category->id)
                                      ->exists();
            if ($hasOtherChildren) {
                $error = ['parent_id' => ['Kategori yang dipilih sudah memiliki sub-kategori lain. Tidak dapat menambah sub-kategori lagi.']];
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $error
                    ], 422);
                }
                
                return redirect()->back()
                    ->withErrors($error)
                    ->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $category->update([
                'type' => $request->type,
                'nama' => $request->nama,
                'warna' => $request->warna,
                'icon' => $request->icon,
                'is_marker' => $request->boolean('is_marker'),
                'deskripsi' => $request->deskripsi,
                'parent_id' => $request->parent_id
            ]);

            DB::commit();

            Log::info('Category updated successfully', [
                'id' => $category->id,
                'type' => $category->type,
                'nama' => $category->nama
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

    public function destroy($id)
    {
        $category = Category::with(['children', 'dataSpatial'])->findOrFail($id);
        
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
     * Helper method to get all children IDs recursively
     */
    private function getChildrenIds($category, &$excludeIds)
    {
        foreach ($category->children as $child) {
            $excludeIds[] = $child->id;
            $this->getChildrenIds($child, $excludeIds);
        }
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
     * API method to get category options for select
     */
    public function getOptions($type)
    {
        $categories = Category::where('type', $type)
                            ->whereNull('parent_id')
                            ->orderBy('nama')
                            ->get(['id', 'nama']);
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    // === HELPER METHODS ===
    
    // private function getChildrenIds($category, &$ids)
    // {
    //     foreach ($category->children as $child) {
    //         $ids[] = $child->id;
    //         $this->getChildrenIds($child, $ids);
    //     }
    // }

    private function buildTree($categories)
    {
        return $categories->map(function($category) {
            return [
                'id' => $category->id,
                'nama' => $category->nama,
                'type' => $category->type,
                'warna' => $category->warna,
                'icon' => $category->icon,
                'is_marker' => $category->is_marker,
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
            'by_type' => Category::select('type', DB::raw('count(*) as total'))
                               ->groupBy('type')
                               ->get(),
            'root_categories' => Category::roots()->count(),
            'child_categories' => Category::whereNotNull('parent_id')->count(),
            'marker_categories' => Category::markers()->count()
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
            'action' => 'required|in:delete,update_type,toggle_marker'
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
            'type' => 'nullable|in:layers,musrenbangs,pokir_dprds,psd,psn',
            'include_children' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create duplicate of main category
            $newCategory = Category::create([
                'type' => $request->type ?? $originalCategory->type,
                'nama' => $request->nama,
                'warna' => $originalCategory->warna,
                'icon' => $originalCategory->icon,
                'is_marker' => $originalCategory->is_marker,
                'deskripsi' => $originalCategory->deskripsi,
                'parent_id' => $originalCategory->parent_id
            ]);

            // Duplicate children if requested
            if ($request->boolean('include_children')) {
                $this->duplicateChildren($originalCategory, $newCategory);
            }

            DB::commit();

            Log::info('Category duplicated successfully', [
                'original_id' => $originalCategory->id,
                'new_id' => $newCategory->id,
                'include_children' => $request->boolean('include_children')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil diduplikasi',
                'category' => $newCategory->load('children')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error duplicating category: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menduplikasi kategori: ' . $e->getMessage()
            ], 500);
        }
    }

    private function duplicateChildren($originalCategory, $newParent)
    {
        foreach ($originalCategory->children as $child) {
            $newChild = Category::create([
                'type' => $newParent->type,
                'nama' => $child->nama,
                'warna' => $child->warna,
                'icon' => $child->icon,
                'is_marker' => $child->is_marker,
                'deskripsi' => $child->deskripsi,
                'parent_id' => $newParent->id
            ]);

            // Recursively duplicate grandchildren
            if ($child->children->count() > 0) {
                $this->duplicateChildren($child, $newChild);
            }
        }
    }

    public function move(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'new_parent_id' => 'nullable|exists:categories,id',
            'new_type' => 'nullable|in:layers,musrenbangs,pokir_dprds,psd,psn'
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

        $callback = function() use ($categories) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Type', 'Nama', 'Warna', 'Icon', 'Is Marker', 
                'Deskripsi', 'Parent ID', 'Parent Nama', 'Created At', 'Updated At'
            ]);
            
            foreach ($categories as $category) {
                fputcsv($file, [
                    $category->id,
                    $category->type,
                    $category->nama,
                    $category->warna,
                    $category->icon,
                    $category->is_marker ? 'Yes' : 'No',
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
            'type' => 'required|in:layers,musrenbangs,pokir_dprds,psd,psn',
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
                'nama' => $item['nama'],
                'warna' => $item['warna'] ?? null,
                'icon' => $item['icon'] ?? null,
                'is_marker' => $item['is_marker'] ?? false,
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
                'nama' => $row[1] ?? '',
                'warna' => $row[2] ?? null,
                'icon' => $row[3] ?? null,
                'is_marker' => ($row[4] ?? '') === 'Yes',
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