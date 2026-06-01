<?php

namespace App\Http\Controllers;

use App\Models\Layer;
use App\Services\LayerService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LayerWebController extends Controller
{
    protected LayerService $layerService;

    public function __construct(LayerService $layerService)
    {
        $this->layerService = $layerService;
    }

    public function index(Request $request)
    {
        $category = $request->get('category');

        $layers = Layer::with(['parent', 'children'])
            ->withCount(['features', 'children'])
            ->when($category, fn($q) => $q->where('category', $category))
            ->orderByRaw('COALESCE(parent_id, id)')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();

        return view('backend.pages.layers.index', compact('layers', 'category'));
    }

    public function getData(Request $request)
    {
        $category = $request->get('category');

        $layers = Layer::with(['parent'])
            ->withCount(['features', 'children'])
            ->when($category, fn($q) => $q->where('category', $category))
            ->orderByRaw('COALESCE(parent_id, id)')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();

        $categoryLabels = [
            'tematik'    => 'Peta Tematik',
            'psd'        => 'Proyek Strategis Daerah',
            'psn'        => 'Proyek Strategis Nasional',
            'pokir'      => 'Pokir DPRD',
            'musrenbang' => 'Usulan Musrenbang',
        ];

        $data = $layers->map(fn($layer) => [
            'id'             => $layer->id,
            'name'           => $layer->name,
            'category'       => $layer->category,
            'category_label' => $categoryLabels[$layer->category] ?? $layer->category,
            'type'           => $layer->type,
            'parent_id'      => $layer->parent_id,
            'parent_name'    => $layer->parent?->name,
            'features_count' => $layer->features_count,
            'children_count' => $layer->children_count,
            'is_active'      => $layer->is_active,
            'color'          => $layer->style['color'] ?? '#3388ff',
            'edit_url'       => route('layers.edit', $layer->id),
            'features_url'   => route('layers.features.index', $layer->id),
            'toggle_url'     => route('layers.toggle-active', $layer->id),
        ]);

        return response()->json([
            'data'  => $data,
            'stats' => [
                'total'    => $layers->count(),
                'active'   => $layers->where('is_active', true)->count(),
                'root'     => $layers->whereNull('parent_id')->count(),
                'features' => $layers->sum('features_count'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $category = $request->get('category');
        $parentLayers = Layer::when($category, fn($q) => $q->where('category', $category))
            ->orderBy('name')
            ->get();

        $categories = [
            'tematik'    => 'Peta Tematik',
            'psd'        => 'Proyek Strategis Daerah',
            'psn'        => 'Proyek Strategis Nasional',
            'pokir'      => 'Pokir DPRD',
            'musrenbang' => 'Usulan Musrenbang',
        ];

        return view('backend.pages.layers.create', compact('category', 'parentLayers', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'type'      => 'required|in:point,line,polygon',
            'category'  => 'required|in:tematik,psd,psn,musrenbang,pokir',
            'parent_id' => 'nullable|exists:layers,id',
            'color'     => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $data['style']     = ['color' => $data['color'] ?? '#3388ff'];
        $data['is_active'] = $request->boolean('is_active', true);
        $data['user_id']   = auth()->id();
        unset($data['color']);

        try {
            $layer = $this->layerService->createLayer($data);
            return redirect()
                ->route('layers.index', ['category' => $layer->category])
                ->with('success', 'Layer "' . $layer->name . '" berhasil ditambahkan.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    public function edit(Layer $layer)
    {
        $parentLayers = Layer::where('id', '!=', $layer->id)
            ->where('category', $layer->category)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $categories = [
            'tematik'    => 'Peta Tematik',
            'psd'        => 'Proyek Strategis Daerah',
            'psn'        => 'Proyek Strategis Nasional',
            'pokir'      => 'Pokir DPRD',
            'musrenbang' => 'Usulan Musrenbang',
        ];

        return view('backend.pages.layers.edit', compact('layer', 'parentLayers', 'categories'));
    }

    public function update(Request $request, Layer $layer)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'type'      => 'required|in:point,line,polygon',
            'category'  => 'required|in:tematik,psd,psn,musrenbang,pokir',
            'parent_id' => 'nullable|exists:layers,id',
            'color'     => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $data['style']     = ['color' => $data['color'] ?? '#3388ff'];
        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['color']);

        try {
            $this->layerService->updateLayer($layer->id, $data);
            return redirect()
                ->route('layers.index', ['category' => $data['category']])
                ->with('success', 'Layer berhasil diperbarui.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    public function destroy(Layer $layer)
    {
        $category = $layer->category;
        try {
            $this->layerService->deleteLayer($layer->id);
            return redirect()
                ->route('layers.index', ['category' => $category])
                ->with('success', 'Layer berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Layer tidak dapat dihapus: ' . $e->getMessage());
        }
    }

    public function toggleActive(Layer $layer)
    {
        $this->layerService->toggleLayerActive($layer->id);
        return response()->json([
            'success'   => true,
            'is_active' => $layer->fresh()->is_active,
        ]);
    }

    public function move(Request $request, Layer $layer)
    {
        $parentId = $request->input('parent_id');

        if ($parentId !== null && (int) $parentId === $layer->id) {
            return response()->json(['success' => false, 'message' => 'Layer tidak bisa menjadi parent-nya sendiri.'], 422);
        }

        // Cek circular reference: parent_id tidak boleh merupakan descendant dari layer ini
        if ($parentId) {
            $check = Layer::find((int) $parentId);
            while ($check) {
                if ($check->parent_id === $layer->id) {
                    return response()->json(['success' => false, 'message' => 'Circular reference terdeteksi.'], 422);
                }
                $check = $check->parent;
            }
        }

        $layer->parent_id = $parentId ? (int) $parentId : null;
        $layer->save();

        return response()->json(['success' => true]);
    }
}
