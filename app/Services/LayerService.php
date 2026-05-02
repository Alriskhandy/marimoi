<?php

namespace App\Services;

use App\Models\Layer;
use App\Repositories\LayerRepository;
use App\Repositories\FeatureRepository;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LayerService
{
    protected $layerRepository;
    protected $featureRepository;

    public function __construct(
        LayerRepository $layerRepository,
        FeatureRepository $featureRepository
    ) {
        $this->layerRepository = $layerRepository;
        $this->featureRepository = $featureRepository;
    }

    /**
     * Get all layers with filters
     */
    public function getAllLayers(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return $this->layerRepository->getAll($filters, $perPage);
    }

    /**
     * Get layer by ID
     */
    public function getLayerById(int $id): ?Layer
    {
        return $this->layerRepository->findById($id);
    }

    /**
     * Get root layers
     */
    public function getRootLayers(): SupportCollection
    {
        return $this->layerRepository->getRootLayers();
    }

    /**
     * Get layer children
     */
    public function getLayerChildren(int $parentId): SupportCollection
    {
        return $this->layerRepository->getChildren($parentId);
    }

    /**
     * Get layer tree
     */
    public function getLayerTree(): SupportCollection
    {
        return $this->layerRepository->getLayerTree();
    }

    /**
     * Create new layer with validation
     */
    public function createLayer(array $data): Layer
    {
        $this->validateLayerData($data);

        // Prevent circular reference
        if (isset($data['parent_id']) && $data['parent_id']) {
            $this->checkCircularReference($data['parent_id']);
        }

        return $this->layerRepository->create($data);
    }

    /**
     * Update layer with validation
     */
    public function updateLayer(int $id, array $data): bool
    {
        $this->validateLayerData($data, $id);

        // Prevent circular reference
        if (isset($data['parent_id']) && $data['parent_id']) {
            $this->checkCircularReference($data['parent_id'], $id);
        }

        return $this->layerRepository->update($id, $data);
    }

    /**
     * Delete layer (only if no children and no features)
     */
    public function deleteLayer(int $id): bool
    {
        return $this->layerRepository->delete($id);
    }

    /**
     * Get layers by user
     */
    public function getLayersByUser(int $userId): SupportCollection
    {
        return $this->layerRepository->getByUser($userId);
    }

    /**
     * Get active layers
     */
    public function getActiveLayers(): SupportCollection
    {
        return $this->layerRepository->getActiveLayers();
    }

    /**
     * Toggle layer active status
     */
    public function toggleLayerActive(int $id): bool
    {
        return $this->layerRepository->toggleActive($id);
    }

    /**
     * Get layers by type
     */
    public function getLayersByType(string $type): SupportCollection
    {
        return $this->layerRepository->getByType($type);
    }

    /**
     * Get layers by category
     */
    public function getLayersByCategory(string $category): SupportCollection
    {
        return $this->layerRepository->getByCategory($category);
    }

    /**
     * Move layer to new parent
     */
    public function moveLayerToParent(int $layerId, ?int $newParentId): bool
    {
        return $this->layerRepository->moveToParent($layerId, $newParentId);
    }

    /**
     * Get layer with features count
     */
    public function getLayerWithFeaturesCount(int $id): ?array
    {
        $layer = $this->getLayerById($id);
        if (!$layer) {
            return null;
        }

        return [
            'layer' => $layer,
            'features_count' => $layer->features()->count(),
            'children_count' => $layer->children()->count(),
            'total_descendants' => $this->getTotalDescendants($layer),
        ];
    }

    /**
     * Get layers statistics
     */
    public function getLayersStatistics(): array
    {
        return $this->layerRepository->getStatistics();
    }

    /**
     * Bulk create layers
     */
    public function bulkCreateLayers(array $layersData): SupportCollection
    {
        $createdLayers = collect();

        DB::beginTransaction();
        try {
            foreach ($layersData as $layerData) {
                $layer = $this->createLayer($layerData);
                $createdLayers->push($layer);
            }

            DB::commit();
            return $createdLayers;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Duplicate layer with all its features
     */
    public function duplicateLayer(int $layerId, array $newLayerData): ?Layer
    {
        $originalLayer = $this->getLayerById($layerId);
        if (!$originalLayer) {
            return null;
        }

        DB::beginTransaction();
        try {
            // Create new layer
            $newLayerData = array_merge([
                'name' => $originalLayer->name . ' (Copy)',
                'type' => $originalLayer->type,
                'category' => $originalLayer->category,
                'style' => $originalLayer->style,
                'parent_id' => $originalLayer->parent_id,
                'is_active' => $originalLayer->is_active,
                'user_id' => $originalLayer->user_id,
            ], $newLayerData);

            $newLayer = $this->createLayer($newLayerData);

            // Duplicate features
            $features = $this->featureRepository->getByLayer($layerId);
            foreach ($features as $feature) {
                $featureData = $feature->toArray();
                unset($featureData['id'], $featureData['created_at'], $featureData['updated_at']);
                $featureData['layer_id'] = $newLayer->id;
                $featureData['uuid'] = (string) \Illuminate\Support\Str::uuid();

                $this->featureRepository->create($featureData);
            }

            DB::commit();
            return $newLayer->load(['features', 'children']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate layer data
     */
    protected function validateLayerData(array $data, int $id = null): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:point,line,polygon',
            'category' => 'required|in:tematik,psd,psn,musrenbang,pokir',
            'style' => 'nullable|array',
            'parent_id' => 'nullable|exists:layers,id',
            'is_active' => 'boolean',
            'user_id' => 'nullable|exists:users,id',
        ];

        // Prevent self-reference
        if ($id && isset($data['parent_id']) && $data['parent_id'] == $id) {
            throw ValidationException::withMessages([
                'parent_id' => 'Layer cannot be its own parent.'
            ]);
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Check for circular reference
     */
    protected function checkCircularReference(int $parentId, int $excludeId = null): void
    {
        $layer = $this->layerRepository->findById($parentId);
        if (!$layer) {
            return;
        }

        $currentId = $parentId;
        while ($currentId) {
            if ($excludeId && $currentId === $excludeId) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Circular reference detected in layer hierarchy.'
                ]);
            }

            $parent = $this->layerRepository->findById($currentId);
            $currentId = $parent ? $parent->parent_id : null;
        }
    }

    /**
     * Get total descendants count
     */
    protected function getTotalDescendants(Layer $layer): int
    {
        $count = $layer->children()->count();

        foreach ($layer->children as $child) {
            $count += $this->getTotalDescendants($child);
        }

        return $count;
    }
}