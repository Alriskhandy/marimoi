<?php

namespace App\Services;

use App\Models\FeatureImage;
use App\Repositories\FeatureImageRepository;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FeatureImageService
{
    protected $featureImageRepository;

    public function __construct(FeatureImageRepository $featureImageRepository)
    {
        $this->featureImageRepository = $featureImageRepository;
    }

    /**
     * Get all images with filters
     */
    public function getAllImages(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return $this->featureImageRepository->getAll($filters, $perPage);
    }

    /**
     * Get image by ID
     */
    public function getImageById(int $id): ?FeatureImage
    {
        return $this->featureImageRepository->findById($id);
    }

    /**
     * Get images by feature
     */
    public function getImagesByFeature(int $featureId): SupportCollection
    {
        return $this->featureImageRepository->getByFeature($featureId);
    }

    /**
     * Get images by layer
     */
    public function getImagesByLayer(int $layerId): SupportCollection
    {
        return $this->featureImageRepository->getByLayer($layerId);
    }

    /**
     * Get images by user
     */
    public function getImagesByUser(int $userId): SupportCollection
    {
        return $this->featureImageRepository->getByUser($userId);
    }

    /**
     * Create new image with validation
     */
    public function createImage(array $data): FeatureImage
    {
        $this->validateImageData($data);

        // Check if feature exists
        $this->validateFeatureExists($data['feature_id']);

        return $this->featureImageRepository->create($data);
    }

    /**
     * Update image with validation
     */
    public function updateImage(int $id, array $data): bool
    {
        $this->validateImageData($data, $id);

        return $this->featureImageRepository->update($id, $data);
    }

    /**
     * Delete image
     */
    public function deleteImage(int $id): bool
    {
        return $this->featureImageRepository->delete($id);
    }

    /**
     * Delete images by feature
     */
    public function deleteImagesByFeature(int $featureId): int
    {
        return $this->featureImageRepository->deleteByFeature($featureId);
    }

    /**
     * Check if image file exists
     */
    public function imageFileExists(int $id): bool
    {
        return $this->featureImageRepository->fileExists($id);
    }

    /**
     * Get image file URL
     */
    public function getImageFileUrl(int $id): ?string
    {
        return $this->featureImageRepository->getFileUrl($id);
    }

    /**
     * Bulk create images for a feature
     */
    public function bulkCreateImages(int $featureId, array $imagesData): SupportCollection
    {
        $this->validateFeatureExists($featureId);

        foreach ($imagesData as $imageData) {
            $this->validateImageData($imageData);
        }

        return $this->featureImageRepository->bulkCreate($featureId, $imagesData);
    }

    /**
     * Upload and create image
     */
    public function uploadAndCreateImage(int $featureId, $file, array $additionalData = []): FeatureImage
    {
        $this->validateFeatureExists($featureId);

        // Validate file
        $this->validateUploadedFile($file);

        // Store file
        $filePath = $file->store('feature-images', 'public');

        $imageData = array_merge($additionalData, [
            'feature_id' => $featureId,
            'file_path' => $filePath,
        ]);

        return $this->createImage($imageData);
    }

    /**
     * Bulk upload images
     */
    public function bulkUploadImages(int $featureId, array $files, array $captions = []): SupportCollection
    {
        $this->validateFeatureExists($featureId);

        $createdImages = collect();

        DB::beginTransaction();
        try {
            foreach ($files as $index => $file) {
                $caption = $captions[$index] ?? null;
                $image = $this->uploadAndCreateImage($featureId, $file, [
                    'caption' => $caption
                ]);
                $createdImages->push($image);
            }

            DB::commit();
            return $createdImages;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get images statistics
     */
    public function getImagesStatistics(): array
    {
        return $this->featureImageRepository->getStatistics();
    }

    /**
     * Clean up orphaned image files
     */
    public function cleanupOrphanedFiles(): int
    {
        $images = $this->featureImageRepository->getAll([], 1000); // Get all images
        $cleanedCount = 0;

        foreach ($images as $image) {
            if ($image->file_path && !Storage::exists($image->file_path)) {
                // File doesn't exist, remove database record
                $this->deleteImage($image->id);
                $cleanedCount++;
            }
        }

        return $cleanedCount;
    }

    /**
     * Validate image data
     */
    protected function validateImageData(array $data, int $id = null): void
    {
        $rules = [
            'feature_id' => 'required|exists:features,id',
            'file_path' => 'required|string',
            'caption' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate uploaded file
     */
    protected function validateUploadedFile($file): void
    {
        $rules = [
            'file' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
        ];

        $validator = Validator::make(['file' => $file], $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate feature exists
     */
    protected function validateFeatureExists(int $featureId): void
    {
        // This would typically use FeatureRepository, but for simplicity we'll assume it exists
        // In a real implementation, you'd inject FeatureRepository and check here
        if (!\App\Models\Feature::find($featureId)) {
            throw ValidationException::withMessages([
                'feature_id' => 'Feature not found.'
            ]);
        }
    }
}