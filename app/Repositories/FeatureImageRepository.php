<?php

namespace App\Repositories;

use App\Models\FeatureImage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Storage;

class FeatureImageRepository
{
    protected $model;

    public function __construct(FeatureImage $model)
    {
        $this->model = $model;
    }

    /**
     * Get all images with optional filters
     */
    public function getAll(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = $this->model->with(['feature.layer', 'feature.user']);

        // Filter by feature_id
        if (isset($filters['feature_id'])) {
            $query->where('feature_id', $filters['feature_id']);
        }

        // Filter by feature's layer_id
        if (isset($filters['layer_id'])) {
            $query->whereHas('feature', function ($q) use ($filters) {
                $q->where('layer_id', $filters['layer_id']);
            });
        }

        // Filter by feature's user_id
        if (isset($filters['user_id'])) {
            $query->whereHas('feature', function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id']);
            });
        }

        // Search in caption
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('caption', 'ILIKE', "%{$search}%");
        }

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get image by ID
     */
    public function findById(int $id): ?FeatureImage
    {
        return $this->model->with(['feature.layer', 'feature.user'])->find($id);
    }

    /**
     * Get images by feature
     */
    public function getByFeature(int $featureId): Collection
    {
        return $this->model->where('feature_id', $featureId)
                          ->orderBy('created_at', 'desc')
                          ->get();
    }

    /**
     * Get images by layer
     */
    public function getByLayer(int $layerId): Collection
    {
        return $this->model->whereHas('feature', function ($q) use ($layerId) {
            $q->where('layer_id', $layerId);
        })->with(['feature'])->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get images by user
     */
    public function getByUser(int $userId): Collection
    {
        return $this->model->whereHas('feature', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->with(['feature.layer'])->orderBy('created_at', 'desc')->get();
    }

    /**
     * Create new image
     */
    public function create(array $data): FeatureImage
    {
        return $this->model->create($data);
    }

    /**
     * Update image
     */
    public function update(int $id, array $data): bool
    {
        $image = $this->findById($id);
        if (!$image) {
            return false;
        }

        return $image->update($data);
    }

    /**
     * Delete image
     */
    public function delete(int $id): bool
    {
        $image = $this->findById($id);
        if (!$image) {
            return false;
        }

        // Delete physical file if exists
        if ($image->file_path && Storage::exists($image->file_path)) {
            Storage::delete($image->file_path);
        }

        return $image->delete();
    }

    /**
     * Delete images by feature
     */
    public function deleteByFeature(int $featureId): int
    {
        $images = $this->getByFeature($featureId);
        $deletedCount = 0;

        foreach ($images as $image) {
            if ($this->delete($image->id)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_images' => $this->model->count(),
            'images_by_feature' => $this->model->selectRaw('feature_id, COUNT(*) as count')
                                            ->groupBy('feature_id')
                                            ->having('count', '>', 1)
                                            ->pluck('count', 'feature_id')
                                            ->toArray(),
            'features_with_images' => $this->model->distinct('feature_id')->count('feature_id'),
        ];
    }

    /**
     * Check if file exists
     */
    public function fileExists(int $id): bool
    {
        $image = $this->findById($id);
        if (!$image || !$image->file_path) {
            return false;
        }

        return Storage::exists($image->file_path);
    }

    /**
     * Get file URL
     */
    public function getFileUrl(int $id): ?string
    {
        $image = $this->findById($id);
        if (!$image || !$image->file_path) {
            return null;
        }

        return Storage::url($image->file_path);
    }

    /**
     * Bulk create images for a feature
     */
    public function bulkCreate(int $featureId, array $imagesData): SupportCollection
    {
        $createdImages = collect();

        foreach ($imagesData as $imageData) {
            $imageData['feature_id'] = $featureId;
            $createdImages->push($this->create($imageData));
        }

        return $createdImages;
    }
}