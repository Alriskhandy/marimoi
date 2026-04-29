<?php

namespace App\Http\Controllers;

use App\Services\FeatureService;
use App\Services\FeatureImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class FeatureController extends Controller
{
    protected $featureService;
    protected $featureImageService;

    public function __construct(
        FeatureService $featureService,
        FeatureImageService $featureImageService
    ) {
        $this->featureService = $featureService;
        $this->featureImageService = $featureImageService;
    }

    /**
     * Display a listing of features
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'layer_id', 'user_id', 'year', 'search', 'layer_type'
            ]);

            $perPage = $request->get('per_page', 50);
            $perPage = min($perPage, 500); // Max 500 per page

            $features = $this->featureService->getAllFeatures($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $features,
                'meta' => [
                    'total' => $features->total(),
                    'per_page' => $features->perPage(),
                    'current_page' => $features->currentPage(),
                    'last_page' => $features->lastPage(),
                    'from' => $features->firstItem(),
                    'to' => $features->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve features', 500, $e->getMessage());
        }
    }

    /**
     * Store a newly created feature
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();

            $feature = $this->featureService->createFeature($data);

            return response()->json([
                'success' => true,
                'message' => 'Feature created successfully',
                'data' => $feature
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create feature', 500, $e->getMessage());
        }
    }

    /**
     * Display the specified feature
     */
    public function show(int $id): JsonResponse
    {
        try {
            $feature = $this->featureService->getFeatureById($id);

            if (!$feature) {
                return $this->errorResponse('Feature not found', 404);
            }

            return response()->json([
                'success' => true,
                'data' => $feature
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve feature', 500, $e->getMessage());
        }
    }

    /**
     * Update the specified feature
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->all();

            $updated = $this->featureService->updateFeature($id, $data);

            if (!$updated) {
                return $this->errorResponse('Feature not found or update failed', 404);
            }

            $feature = $this->featureService->getFeatureById($id);

            return response()->json([
                'success' => true,
                'message' => 'Feature updated successfully',
                'data' => $feature
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update feature', 500, $e->getMessage());
        }
    }

    /**
     * Remove the specified feature
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->featureService->deleteFeature($id);

            if (!$deleted) {
                return $this->errorResponse('Feature not found or delete failed', 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Feature deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete feature', 500, $e->getMessage());
        }
    }

    /**
     * Get feature by UUID
     */
    public function showByUuid(string $uuid): JsonResponse
    {
        try {
            $feature = $this->featureService->getFeatureByUuid($uuid);

            if (!$feature) {
                return $this->errorResponse('Feature not found', 404);
            }

            return response()->json([
                'success' => true,
                'data' => $feature
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve feature', 500, $e->getMessage());
        }
    }

    /**
     * Get features by layer
     */
    public function getByLayer(int $layerId): JsonResponse
    {
        try {
            $features = $this->featureService->getFeaturesByLayer($layerId);

            return response()->json([
                'success' => true,
                'data' => $features
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve features', 500, $e->getMessage());
        }
    }

    /**
     * Get features by user
     */
    public function getByUser(int $userId): JsonResponse
    {
        try {
            $features = $this->featureService->getFeaturesByUser($userId);

            return response()->json([
                'success' => true,
                'data' => $features
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve features', 500, $e->getMessage());
        }
    }

    /**
     * Get features within bounding box
     */
    public function getWithinBounds(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'min_lng' => 'required|numeric|between:-180,180',
                'min_lat' => 'required|numeric|between:-90,90',
                'max_lng' => 'required|numeric|between:-180,180',
                'max_lat' => 'required|numeric|between:-90,90',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse(new ValidationException($validator));
            }

            $features = $this->featureService->getFeaturesWithinBounds(
                $request->min_lng,
                $request->min_lat,
                $request->max_lng,
                $request->max_lat
            );

            return response()->json([
                'success' => true,
                'data' => $features
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve features', 500, $e->getMessage());
        }
    }

    /**
     * Increment feature views
     */
    public function incrementViews(int $id): JsonResponse
    {
        try {
            $incremented = $this->featureService->incrementFeatureViews($id);

            if (!$incremented) {
                return $this->errorResponse('Feature not found', 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Feature views incremented'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to increment views', 500, $e->getMessage());
        }
    }

    /**
     * Get feature images
     */
    public function getImages(int $id): JsonResponse
    {
        try {
            $feature = $this->featureService->getFeatureById($id);

            if (!$feature) {
                return $this->errorResponse('Feature not found', 404);
            }

            $images = $this->featureImageService->getImagesByFeature($id);

            return response()->json([
                'success' => true,
                'data' => $images
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve images', 500, $e->getMessage());
        }
    }

    /**
     * Add image to feature
     */
    public function addImage(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->only(['file_path', 'caption']);

            $feature = $this->featureService->addImageToFeature($id, $data);

            if (!$feature) {
                return $this->errorResponse('Feature not found', 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Image added successfully',
                'data' => $feature
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add image', 500, $e->getMessage());
        }
    }

    /**
     * Remove image from feature
     */
    public function removeImage(int $featureId, int $imageId): JsonResponse
    {
        try {
            $removed = $this->featureService->removeImageFromFeature($featureId, $imageId);

            if (!$removed) {
                return $this->errorResponse('Feature or image not found', 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Image removed successfully'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to remove image', 500, $e->getMessage());
        }
    }

    /**
     * Get feature with images count
     */
    public function getWithImagesCount(int $id): JsonResponse
    {
        try {
            $data = $this->featureService->getFeatureWithImagesCount($id);

            if (!$data) {
                return $this->errorResponse('Feature not found', 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve feature data', 500, $e->getMessage());
        }
    }

    /**
     * Bulk create features
     */
    public function bulkStore(Request $request): JsonResponse
    {
        try {
            $featuresData = $request->get('features', []);

            if (!is_array($featuresData) || empty($featuresData)) {
                return $this->errorResponse('Features data is required', 400);
            }

            $features = $this->featureService->bulkCreateFeatures($featuresData);

            return response()->json([
                'success' => true,
                'message' => 'Features created successfully',
                'data' => $features,
                'count' => $features->count()
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create features', 500, $e->getMessage());
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
