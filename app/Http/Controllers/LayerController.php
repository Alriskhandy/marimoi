<?php

namespace App\Http\Controllers;

use App\Services\LayerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LayerController extends Controller
{
    protected $layerService;

    public function __construct(LayerService $layerService)
    {
        $this->layerService = $layerService;
    }

    /**
     * Display a listing of layers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'type', 'user_id', 'is_active', 'parent_id', 'search'
            ]);

            $perPage = $request->get('per_page', 50);
            $perPage = min($perPage, 500);

            $layers = $this->layerService->getAllLayers($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $layers,
                'meta' => [
                    'total' => $layers->total(),
                    'per_page' => $layers->perPage(),
                    'current_page' => $layers->currentPage(),
                    'last_page' => $layers->lastPage(),
                    'from' => $layers->firstItem(),
                    'to' => $layers->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve layers', 500, $e->getMessage());
        }
    }

    /**
     * Store a newly created layer
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();

            $layer = $this->layerService->createLayer($data);

            return response()->json([
                'success' => true,
                'message' => 'Layer created successfully',
                'data' => $layer
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create layer', 500, $e->getMessage());
        }
    }

    /**
     * Display the specified layer
     */
    public function show(int $id): JsonResponse
    {
        try {
            $layer = $this->layerService->getLayerById($id);

            if (!$layer) {
                return $this->errorResponse('Layer not found', 404);
            }

            return response()->json([
                'success' => true,
                'data' => $layer
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve layer', 500, $e->getMessage());
        }
    }

    /**
     * Update the specified layer
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->all();

            $updated = $this->layerService->updateLayer($id, $data);

            if (!$updated) {
                return $this->errorResponse('Layer not found or update failed', 404);
            }

            $layer = $this->layerService->getLayerById($id);

            return response()->json([
                'success' => true,
                'message' => 'Layer updated successfully',
                'data' => $layer
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update layer', 500, $e->getMessage());
        }
    }

    /**
     * Remove the specified layer
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->layerService->deleteLayer($id);

            if (!$deleted) {
                return $this->errorResponse('Layer not found or cannot be deleted (has children or features)', 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Layer deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete layer', 500, $e->getMessage());
        }
    }

    /**
     * Get root layers
     */
    public function getRootLayers(): JsonResponse
    {
        try {
            $layers = $this->layerService->getRootLayers();

            return response()->json([
                'success' => true,
                'data' => $layers
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve root layers', 500, $e->getMessage());
        }
    }

    /**
     * Get layer children
     */
    public function getChildren(int $parentId): JsonResponse
    {
        try {
            $layers = $this->layerService->getLayerChildren($parentId);

            return response()->json([
                'success' => true,
                'data' => $layers
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve layer children', 500, $e->getMessage());
        }
    }

    /**
     * Get layer tree
     */
    public function getLayerTree(): JsonResponse
    {
        try {
            $layers = $this->layerService->getLayerTree();

            return response()->json([
                'success' => true,
                'data' => $layers
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve layer tree', 500, $e->getMessage());
        }
    }

    /**
     * Get layers by user
     */
    public function getByUser(int $userId): JsonResponse
    {
        try {
            $layers = $this->layerService->getLayersByUser($userId);

            return response()->json([
                'success' => true,
                'data' => $layers
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve layers', 500, $e->getMessage());
        }
    }

    /**
     * Get active layers
     */
    public function getActiveLayers(): JsonResponse
    {
        try {
            $layers = $this->layerService->getActiveLayers();

            return response()->json([
                'success' => true,
                'data' => $layers
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve active layers', 500, $e->getMessage());
        }
    }

    /**
     * Toggle layer active status
     */
    public function toggleActive(int $id): JsonResponse
    {
        try {
            $toggled = $this->layerService->toggleLayerActive($id);

            if (!$toggled) {
                return $this->errorResponse('Layer not found', 404);
            }

            $layer = $this->layerService->getLayerById($id);

            return response()->json([
                'success' => true,
                'message' => 'Layer active status toggled',
                'data' => $layer
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to toggle layer status', 500, $e->getMessage());
        }
    }

    /**
     * Get layers by type
     */
    public function getByType(string $type): JsonResponse
    {
        try {
            $layers = $this->layerService->getLayersByType($type);

            return response()->json([
                'success' => true,
                'data' => $layers
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve layers', 500, $e->getMessage());
        }
    }

    /**
     * Move layer to new parent
     */
    public function moveToParent(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'parent_id' => 'nullable|integer|exists:layers,id'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse(new ValidationException($validator));
            }

            $moved = $this->layerService->moveLayerToParent($id, $request->parent_id);

            if (!$moved) {
                return $this->errorResponse('Layer not found or circular reference detected', 400);
            }

            $layer = $this->layerService->getLayerById($id);

            return response()->json([
                'success' => true,
                'message' => 'Layer moved successfully',
                'data' => $layer
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to move layer', 500, $e->getMessage());
        }
    }

    /**
     * Get layer with features count
     */
    public function getWithFeaturesCount(int $id): JsonResponse
    {
        try {
            $data = $this->layerService->getLayerWithFeaturesCount($id);

            if (!$data) {
                return $this->errorResponse('Layer not found', 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve layer data', 500, $e->getMessage());
        }
    }

    /**
     * Get layers statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $statistics = $this->layerService->getLayersStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics', 500, $e->getMessage());
        }
    }

    /**
     * Duplicate layer
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->only(['name', 'type', 'style', 'parent_id', 'is_active', 'user_id']);

            $layer = $this->layerService->duplicateLayer($id, $data);

            if (!$layer) {
                return $this->errorResponse('Original layer not found', 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Layer duplicated successfully',
                'data' => $layer
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to duplicate layer', 500, $e->getMessage());
        }
    }

    /**
     * Bulk create layers
     */
    public function bulkStore(Request $request): JsonResponse
    {
        try {
            $layersData = $request->get('layers', []);

            if (!is_array($layersData) || empty($layersData)) {
                return $this->errorResponse('Layers data is required', 400);
            }

            $layers = $this->layerService->bulkCreateLayers($layersData);

            return response()->json([
                'success' => true,
                'message' => 'Layers created successfully',
                'data' => $layers,
                'count' => $layers->count()
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create layers', 500, $e->getMessage());
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
