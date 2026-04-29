<?php

namespace App\Repositories;

use App\Models\Feature;
use App\Models\FeatureImage;
use App\Models\Layer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FeatureRepository
{
    protected $model;

    public function __construct(Feature $model)
    {
        $this->model = $model;
    }

    /**
     * Get all features with optional filters
     */
    public function getAll(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = $this->model->with(['layer', 'user', 'images']);

        // Filter by layer_id
        if (isset($filters['layer_id'])) {
            $query->where('layer_id', $filters['layer_id']);
        }

        // Filter by user_id
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by year
        if (isset($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        // Search in properties (JSONB)
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereRaw("properties::text ILIKE ?", ["%{$search}%"])
                  ->orWhere('uuid', 'ILIKE', "%{$search}%");
            });
        }

        // Filter by layer type
        if (isset($filters['layer_type'])) {
            $query->whereHas('layer', function ($q) use ($filters) {
                $q->where('type', $filters['layer_type']);
            });
        }

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get feature by ID
     */
    public function findById(int $id): ?Feature
    {
        return $this->model->with(['layer', 'user', 'images'])->find($id);
    }

    /**
     * Get feature by UUID
     */
    public function findByUuid(string $uuid): ?Feature
    {
        return $this->model->with(['layer', 'user', 'images'])->where('uuid', $uuid)->first();
    }

    /**
     * Create new feature
     */
    public function create(array $data): Feature
    {
        return $this->model->create($data);
    }

    /**
     * Update feature
     */
    public function update(int $id, array $data): bool
    {
        $feature = $this->findById($id);
        if (!$feature) {
            return false;
        }

        return $feature->update($data);
    }

    /**
     * Delete feature
     */
    public function delete(int $id): bool
    {
        $feature = $this->findById($id);
        if (!$feature) {
            return false;
        }

        return $feature->delete();
    }

    /**
     * Get features by layer
     */
    public function getByLayer(int $layerId): Collection
    {
        return $this->model->where('layer_id', $layerId)->with(['user', 'images'])->get();
    }

    /**
     * Get features by user
     */
    public function getByUser(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)->with(['layer', 'images'])->get();
    }

    /**
     * Increment views count
     */
    public function incrementViews(int $id): bool
    {
        return $this->model->where('id', $id)->increment('views');
    }

    /**
     * Get features within bounding box (spatial query)
     */
    public function getWithinBounds(float $minLng, float $minLat, float $maxLng, float $maxLat): Collection
    {
        $bbox = "ST_MakeEnvelope({$minLng}, {$minLat}, {$maxLng}, {$maxLat}, 4326)";

        return $this->model->whereRaw("ST_Intersects(geom, {$bbox})")
                          ->with(['layer', 'user'])
                          ->get();
    }

    /**
     * Delete feature with images
     */
    public function deleteWithImages(int $id): bool
    {
        $feature = $this->findById($id);
        if (!$feature) {
            return false;
        }

        // Delete associated images
        foreach ($feature->images as $image) {
            if ($image->file_path && Storage::exists($image->file_path)) {
                Storage::delete($image->file_path);
            }
            $image->delete();
        }

        return $feature->delete();
    }

    /**
     * Add image to feature
     */
    public function addImage(int $featureId, array $imageData): ?FeatureImage
    {
        $feature = $this->findById($featureId);
        if (!$feature) {
            return null;
        }

        $imageData['feature_id'] = $featureId;
        return $feature->images()->create($imageData);
    }

    /**
     * Remove image from feature
     */
    public function removeImage(int $featureId, int $imageId): bool
    {
        $feature = $this->findById($featureId);
        if (!$feature) {
            return false;
        }

        $image = $feature->images()->find($imageId);
        if (!$image) {
            return false;
        }

        // Delete physical file
        if ($image->file_path && Storage::exists($image->file_path)) {
            Storage::delete($image->file_path);
        }

        return $image->delete();
    }

    /**
     * Get feature with images count
     */
    public function getWithImagesCount(int $id): ?array
    {
        $feature = $this->findById($id);
        if (!$feature) {
            return null;
        }

        return [
            'feature' => $feature,
            'images_count' => $feature->images()->count(),
            'images' => $feature->images,
        ];
    }
}