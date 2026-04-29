<?php

namespace App\Http\Controllers;

use App\Services\FeatureImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FeatureImageController extends Controller
{
    protected $featureImageService;

    public function __construct(FeatureImageService $featureImageService)
    {
        $this->featureImageService = $featureImageService;
    }

    /**
     * Display a listing of images
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'feature_id', 'layer_id', 'user_id', 'search'
            ]);

            $perPage = $request->get('per_page', 50);
            $perPage = min($perPage, 500);

            $images = $this->featureImageService->getAllImages($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $images,
                'meta' => [
                    'total' => $images->total(),
                    'per_page' => $images->perPage(),
                    'current_page' => $images->currentPage(),
                    'last_page' => $images->lastPage(),
                    'from' => $images->firstItem(),
                    'to' => $images->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve images', 500, $e->getMessage());
        }
    }

    /**
     * Store a newly created image
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->only(['feature_id', 'file_path', 'caption']);

            $image = $this->featureImageService->createImage($data);

            return response()->json([
                'success' => true,
                'message' => 'Image created successfully',
                'data' => $image
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create image', 500, $e->getMessage());
        }
    }

    /**
     * Display the specified image
     */
    public function show(int $id): JsonResponse
    {
        try {
            $image = $this->featureImageService->getImageById($id);

            if (!$image) {
                return $this->errorResponse('Image not found', 404);
            }

            return response()->json([
                'success' => true,
                'data' => $image
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve image', 500, $e->getMessage());
        }
    }

    /**
     * Update the specified image
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->only(['caption']);

            $updated = $this->featureImageService->updateImage($id, $data);

            if (!$updated) {
                return $this->errorResponse('Image not found or update failed', 404);
            }

            $image = $this->featureImageService->getImageById($id);

            return response()->json([
                'success' => true,
                'message' => 'Image updated successfully',
                'data' => $image
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update image', 500, $e->getMessage());
        }
    }

    /**
     * Remove the specified image
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->featureImageService->deleteImage($id);

            if (!$deleted) {
                return $this->errorResponse('Image not found or delete failed', 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete image', 500, $e->getMessage());
        }
    }

    /**
     * Get images by feature
     */
    public function getByFeature(int $featureId): JsonResponse
    {
        try {
            $images = $this->featureImageService->getImagesByFeature($featureId);

            return response()->json([
                'success' => true,
                'data' => $images
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve images', 500, $e->getMessage());
        }
    }

    /**
     * Get images by layer
     */
    public function getByLayer(int $layerId): JsonResponse
    {
        try {
            $images = $this->featureImageService->getImagesByLayer($layerId);

            return response()->json([
                'success' => true,
                'data' => $images
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve images', 500, $e->getMessage());
        }
    }

    /**
     * Get images by user
     */
    public function getByUser(int $userId): JsonResponse
    {
        try {
            $images = $this->featureImageService->getImagesByUser($userId);

            return response()->json([
                'success' => true,
                'data' => $images
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve images', 500, $e->getMessage());
        }
    }

    /**
     * Upload and create image
     */
    public function upload(Request $request, int $featureId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
                'caption' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse(new ValidationException($validator));
            }

            $image = $this->featureImageService->uploadAndCreateImage(
                $featureId,
                $request->file('image'),
                ['caption' => $request->caption]
            );

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => $image
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload image', 500, $e->getMessage());
        }
    }

    /**
     * Bulk upload images
     */
    public function bulkUpload(Request $request, int $featureId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'images' => 'required|array|min:1|max:10',
                'images.*' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
                'captions' => 'nullable|array',
                'captions.*' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse(new ValidationException($validator));
            }

            $images = $this->featureImageService->bulkUploadImages(
                $featureId,
                $request->file('images'),
                $request->captions ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Images uploaded successfully',
                'data' => $images,
                'count' => $images->count()
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload images', 500, $e->getMessage());
        }
    }

    /**
     * Bulk create images
     */
    public function bulkStore(Request $request): JsonResponse
    {
        try {
            $featureId = $request->get('feature_id');
            $imagesData = $request->get('images', []);

            if (!$featureId) {
                return $this->errorResponse('Feature ID is required', 400);
            }

            if (!is_array($imagesData) || empty($imagesData)) {
                return $this->errorResponse('Images data is required', 400);
            }

            $images = $this->featureImageService->bulkCreateImages($featureId, $imagesData);

            return response()->json([
                'success' => true,
                'message' => 'Images created successfully',
                'data' => $images,
                'count' => $images->count()
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create images', 500, $e->getMessage());
        }
    }

    /**
     * Check if image file exists
     */
    public function checkFileExists(int $id): JsonResponse
    {
        try {
            $exists = $this->featureImageService->imageFileExists($id);

            return response()->json([
                'success' => true,
                'exists' => $exists
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to check file existence', 500, $e->getMessage());
        }
    }

    /**
     * Get image file URL
     */
    public function getFileUrl(int $id): JsonResponse
    {
        try {
            $url = $this->featureImageService->getImageFileUrl($id);

            if (!$url) {
                return $this->errorResponse('Image not found', 404);
            }

            return response()->json([
                'success' => true,
                'url' => $url
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get file URL', 500, $e->getMessage());
        }
    }

    /**
     * Get images statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $statistics = $this->featureImageService->getImagesStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics', 500, $e->getMessage());
        }
    }

    /**
     * Cleanup orphaned image files
     */
    public function cleanupOrphanedFiles(): JsonResponse
    {
        try {
            $cleanedCount = $this->featureImageService->cleanupOrphanedFiles();

            return response()->json([
                'success' => true,
                'message' => 'Cleanup completed',
                'cleaned_files' => $cleanedCount
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cleanup files', 500, $e->getMessage());
        }
    }

    /**
     * Helper method for error responses
     */
    protected function errorResponse(string $message, int $status = 400, string $error = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($error && config('app.debug')) {
            $response['error'] = $error;
        }

        return response()->json($response, $status);
    }

    /**
     * Helper method for validation error responses
     */
    protected function validationErrorResponse(ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    }
}