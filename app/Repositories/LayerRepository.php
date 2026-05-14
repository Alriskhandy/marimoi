<?php

namespace App\Repositories;

use App\Models\Layer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LayerRepository
{
    protected $model;

    public function __construct(Layer $model)
    {
        $this->model = $model;
    }

    /**
     * Get all layers with optional filters
     */
    public function getAll(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = $this->model->with(['parent', 'children', 'user', 'features']);

        // Filter by type
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by category
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Filter by user_id
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by is_active
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Filter by parent_id (null for root layers)
        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        // Search in name
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'ILIKE', "%{$search}%");
        }

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get layer by ID
     */
    public function findById(int $id): ?Layer
    {
        return $this->model->with(['parent', 'children', 'user', 'features'])->find($id);
    }

    /**
     * Get root layers (no parent)
     */
    public function getRootLayers(): Collection
    {
        return $this->model->whereNull('parent_id')
                          ->with(['children', 'user'])
                          ->orderBy('name')
                          ->get();
    }

    /**
     * Get children of a layer
     */
    public function getChildren(int $parentId): Collection
    {
        return $this->model->where('parent_id', $parentId)
                          ->with(['children', 'user'])
                          ->orderBy('name')
                          ->get();
    }

    /**
     * Get layer tree (nested)
     */
    public function getLayerTree(): Collection
    {
        return $this->model->whereNull('parent_id')
                          ->with(['children'])
                          ->orderBy('category')
                          ->get();
    }

    /**
     * Create new layer
     */
    public function create(array $data): Layer
    {
        return $this->model->create($data);
    }

    /**
     * Update layer
     */
    public function update(int $id, array $data): bool
    {
        $layer = $this->findById($id);
        if (!$layer) {
            return false;
        }

        return $layer->update($data);
    }

    /**
     * Delete layer
     */
    public function delete(int $id): bool
    {
        $layer = $this->findById($id);
        if (!$layer) {
            return false;
        }

        // Check if has children or features
        if ($layer->children()->exists() || $layer->features()->exists()) {
            return false; // Prevent deletion if has dependencies
        }

        return $layer->delete();
    }

    /**
     * Get layers by user
     */
    public function getByUser(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)
                          ->with(['parent', 'children'])
                          ->orderBy('name')
                          ->get();
    }

    /**
     * Get active layers
     */
    public function getActiveLayers(): Collection
    {
        return $this->model->where('is_active', true)
                          ->with(['parent', 'children', 'user'])
                          ->orderBy('name')
                          ->get();
    }

    /**
     * Toggle layer active status
     */
    public function toggleActive(int $id): bool
    {
        $layer = $this->findById($id);
        if (!$layer) {
            return false;
        }

        return $layer->update(['is_active' => !$layer->is_active]);
    }

    /**
     * Get layers by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('type', $type)
                          ->where('is_active', true)
                          ->with(['user', 'features'])
                          ->orderBy('name')
                          ->get();
    }

    /**
     * Get layers by category
     */
    public function getByCategory(string $category): Collection
    {
        return $this->model->where('category', $category)
                          ->with(['parent', 'children',])
                          ->orderBy('name')
                          ->get();
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_layers' => $this->model->count(),
            'active_layers' => $this->model->where('is_active', true)->count(),
            'layers_by_type' => $this->model->selectRaw('type, COUNT(*) as count')
                                          ->groupBy('type')
                                          ->pluck('count', 'type')
                                          ->toArray(),
            'layers_by_category' => $this->model->selectRaw('category, COUNT(*) as count')
                                              ->whereNotNull('category')
                                              ->groupBy('category')
                                              ->pluck('count', 'category')
                                              ->toArray(),
            'root_layers' => $this->model->whereNull('parent_id')->count(),
            'layers_with_features' => $this->model->has('features')->count(),
        ];
    }

    /**
     * Move layer to new parent
     */
    public function moveToParent(int $layerId, ?int $newParentId): bool
    {
        $layer = $this->findById($layerId);
        if (!$layer) {
            return false;
        }

        // Prevent circular reference
        if ($newParentId && $this->wouldCreateCircularReference($layerId, $newParentId)) {
            return false;
        }

        return $layer->update(['parent_id' => $newParentId]);
    }

    /**
     * Check if moving would create circular reference
     */
    private function wouldCreateCircularReference(int $layerId, int $newParentId): bool
    {
        $currentId = $newParentId;
        while ($currentId) {
            if ($currentId === $layerId) {
                return true;
            }
            $parent = $this->model->find($currentId);
            $currentId = $parent ? $parent->parent_id : null;
        }
        return false;
    }
}