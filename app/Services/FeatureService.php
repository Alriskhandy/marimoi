<?php

namespace App\Services;

use App\Models\Feature;
use App\Repositories\FeatureRepository;
use App\Repositories\FeatureImageRepository;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FeatureService
{
    protected $featureRepository;
    protected $featureImageRepository;

    public function __construct(
        FeatureRepository $featureRepository,
        FeatureImageRepository $featureImageRepository
    ) {
        $this->featureRepository = $featureRepository;
        $this->featureImageRepository = $featureImageRepository;
    }

    /**
     * Get all features with filters
     */
    public function getAllFeatures(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return $this->featureRepository->getAll($filters, $perPage);
    }

    /**
     * Get feature by ID
     */
    public function getFeatureById(int $id): ?Feature
    {
        return $this->featureRepository->findById($id);
    }

    /**
     * Get feature by UUID
     */
    public function getFeatureByUuid(string $uuid): ?Feature
    {
        return $this->featureRepository->findByUuid($uuid);
    }

    /**
     * Create new feature with validation
     */
    public function createFeature(array $data): Feature
    {
        $this->validateFeatureData($data);

        DB::beginTransaction();
        try {
            // Generate UUID if not provided
            if (!isset($data['uuid']) || empty($data['uuid'])) {
                $data['uuid'] = (string) \Illuminate\Support\Str::uuid();
            }

            $feature = $this->featureRepository->create($data);

            // Handle images if provided
            if (isset($data['images']) && is_array($data['images'])) {
                $this->handleFeatureImages($feature->id, $data['images']);
            }

            DB::commit();
            return $feature->load(['layer', 'user', 'images']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update feature with validation
     */
    public function updateFeature(int $id, array $data): bool
    {
        $this->validateFeatureData($data, $id);

        return $this->featureRepository->update($id, $data);
    }

    /**
     * Delete feature
     */
    public function deleteFeature(int $id): bool
    {
        return $this->featureRepository->delete($id);
    }

    /**
     * Delete feature with images
     */
    public function deleteFeatureWithImages(int $id): bool
    {
        return $this->featureRepository->deleteWithImages($id);
    }

    /**
     * Get features by layer
     */
    public function getFeaturesByLayer(int $layerId): SupportCollection
    {
        return $this->featureRepository->getByLayer($layerId);
    }

    /**
     * Get features as GeoJSON rows for a specific layer (paginated).
     */
    public function getGeoJsonByLayer(int $layerId, int $limit = 500, int $offset = 0): SupportCollection
    {
        return $this->featureRepository->getGeoJsonByLayer($layerId, $limit, $offset);
    }

    /**
     * Count features with geometry for a layer.
     */
    public function countGeoJsonByLayer(int $layerId): int
    {
        return $this->featureRepository->countGeoJsonByLayer($layerId);
    }

    /**
     * Get features by user
     */
    public function getFeaturesByUser(int $userId): SupportCollection
    {
        return $this->featureRepository->getByUser($userId);
    }

    /**
     * Increment feature views
     */
    public function incrementFeatureViews(int $id): bool
    {
        return $this->featureRepository->incrementViews($id);
    }

    /**
     * Get features within bounding box
     */
    public function getFeaturesWithinBounds(float $minLng, float $minLat, float $maxLng, float $maxLat): SupportCollection
    {
        return $this->featureRepository->getWithinBounds($minLng, $minLat, $maxLng, $maxLat);
    }

    /**
     * Add image to feature
     */
    public function addImageToFeature(int $featureId, array $imageData): ?Feature
    {
        $this->validateImageData($imageData);

        $image = $this->featureRepository->addImage($featureId, $imageData);
        if ($image) {
            return $this->getFeatureById($featureId);
        }

        return null;
    }

    /**
     * Remove image from feature
     */
    public function removeImageFromFeature(int $featureId, int $imageId): bool
    {
        return $this->featureRepository->removeImage($featureId, $imageId);
    }

    /**
     * Get feature with images count
     */
    public function getFeatureWithImagesCount(int $id): ?array
    {
        return $this->featureRepository->getWithImagesCount($id);
    }

    /**
     * Bulk create features
     */
    public function bulkCreateFeatures(array $featuresData): SupportCollection
    {
        $createdFeatures = collect();

        DB::beginTransaction();
        try {
            foreach ($featuresData as $featureData) {
                $feature = $this->createFeature($featureData);
                $createdFeatures->push($feature);
            }

            DB::commit();
            return $createdFeatures;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate feature data
     */
    protected function validateFeatureData(array $data, int $id = null): void
    {
        $rules = [
            'layer_id' => 'required|exists:layers,id',
            'user_id' => 'nullable|exists:users,id',
            'properties' => 'nullable|array',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
            'uuid' => 'nullable|string|unique:features,uuid' . ($id ? ",$id" : ''),
            'images' => 'nullable|array',
            'images.*.file_path' => 'required_with:images|string',
            'images.*.caption' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate image data
     */
    protected function validateImageData(array $data): void
    {
        $rules = [
            'file_path' => 'required|string',
            'caption' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Handle feature images during creation
     */
    protected function handleFeatureImages(int $featureId, array $imagesData): void
    {
        foreach ($imagesData as $imageData) {
            $this->validateImageData($imageData);
            $this->featureRepository->addImage($featureId, $imageData);
        }
    }
}