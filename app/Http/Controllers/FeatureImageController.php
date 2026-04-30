<?php

namespace App\Http\Controllers;

use App\Services\FeatureImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use OpenApi\Attributes as OA;

#[OA\Tag(name: "Feature Images", description: "Feature image management endpoints")]
#[OA\Schema(
    schema: "FeatureImage",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "feature_id", type: "integer", example: 1),
        new OA\Property(property: "file_path", type: "string", example: "feature-images/abc123.jpg"),
        new OA\Property(property: "caption", type: "string", example: "Sample image caption", nullable: true),
        new OA\Property(property: "file_size", type: "integer", example: 1024000),
        new OA\Property(property: "mime_type", type: "string", example: "image/jpeg"),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
        new OA\Property(property: "feature", ref: "#/components/schemas/Feature", nullable: true)
    ]
)]
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
    #[OA\Get(
        path: "/api/gis/images",
        summary: "Get list of feature images",
        description: "Retrieve a paginated list of feature images with optional filters",
        tags: ["Feature Images"],
        parameters: [
            new OA\Parameter(
                name: "feature_id",
                description: "Filter by feature ID",
                in: "query",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "layer_id",
                description: "Filter by layer ID",
                in: "query",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "user_id",
                description: "Filter by user ID",
                in: "query",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "search",
                description: "Search in caption",
                in: "query",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "per_page",
                description: "Items per page (max 500)",
                in: "query",
                schema: new OA\Schema(type: "integer", default: 50, maximum: 500)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Images retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/FeatureImage")),
                        new OA\Property(property: "meta", ref: "#/components/schemas/PaginationMeta")
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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
    #[OA\Post(
        path: "/api/gis/images",
        summary: "Create a new feature image",
        description: "Create a new feature image record",
        tags: ["Feature Images"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["feature_id", "file_path"],
                properties: [
                    new OA\Property(property: "feature_id", type: "integer", description: "Feature ID"),
                    new OA\Property(property: "file_path", type: "string", description: "File path of the image"),
                    new OA\Property(property: "caption", type: "string", description: "Image caption", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Image created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", ref: "#/components/schemas/FeatureImage")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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
    #[OA\Get(
        path: "/api/gis/images/{id}",
        summary: "Get feature image by ID",
        description: "Retrieve a specific feature image by its ID",
        tags: ["Feature Images"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Image ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Image retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/FeatureImage")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Image not found"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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
    #[OA\Put(
        path: "/api/gis/images/{id}",
        summary: "Update feature image",
        description: "Update an existing feature image",
        tags: ["Feature Images"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Image ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "caption", type: "string", description: "Image caption", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Image updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", ref: "#/components/schemas/FeatureImage")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Image not found"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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
    #[OA\Delete(
        path: "/api/gis/images/{id}",
        summary: "Delete feature image",
        description: "Delete a feature image and its associated file",
        tags: ["Feature Images"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Image ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Image deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Image not found"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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
    #[OA\Get(
        path: "/api/gis/images/feature/{featureId}",
        summary: "Get images by feature",
        description: "Retrieve all images for a specific feature",
        tags: ["Feature Images"],
        parameters: [
            new OA\Parameter(
                name: "featureId",
                description: "Feature ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Images retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/FeatureImage"))
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Feature not found"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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
    #[OA\Get(
        path: "/api/gis/images/layer/{layerId}",
        summary: "Get images by layer",
        description: "Retrieve all images for a specific layer",
        tags: ["Feature Images"],
        parameters: [
            new OA\Parameter(
                name: "layerId",
                description: "Layer ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Images retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/FeatureImage"))
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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
    #[OA\Get(
        path: "/api/gis/images/user/{userId}",
        summary: "Get images by user",
        description: "Retrieve all images uploaded by a specific user",
        tags: ["Feature Images"],
        parameters: [
            new OA\Parameter(
                name: "userId",
                description: "User ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Images retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/FeatureImage"))
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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
    #[OA\Post(
        path: "/api/gis/images/upload/{featureId}",
        summary: "Upload image for a feature",
        description: "Upload an image file and associate it with a feature",
        tags: ["Feature Images"],
        parameters: [
            new OA\Parameter(
                name: "featureId",
                description: "Feature ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["image"],
                    properties: [
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Image file (jpeg, jpg, png, gif, webp, max 5MB)"),
                        new OA\Property(property: "caption", type: "string", description: "Image caption", nullable: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Image uploaded successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", ref: "#/components/schemas/FeatureImage")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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
    #[OA\Post(
        path: "/api/gis/images/bulk-upload/{featureId}",
        summary: "Bulk upload images for a feature",
        description: "Upload multiple image files (max 10) and associate them with a feature",
        tags: ["Feature Images"],
        parameters: [
            new OA\Parameter(
                name: "featureId",
                description: "Feature ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["images"],
                    properties: [
                        new OA\Property(property: "images[]", type: "array", items: new OA\Items(type: "string", format: "binary"), description: "Image files (max 10, each max 5MB)"),
                        new OA\Property(property: "captions[]", type: "array", items: new OA\Items(type: "string"), description: "Optional captions for each image")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Images uploaded successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/FeatureImage")),
                        new OA\Property(property: "count", type: "integer")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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
    #[OA\Post(
        path: "/api/gis/images/bulk",
        summary: "Bulk create feature images",
        description: "Create multiple feature image records using file paths",
        tags: ["Feature Images"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["feature_id", "images"],
                properties: [
                    new OA\Property(property: "feature_id", type: "integer", description: "Feature ID"),
                    new OA\Property(
                        property: "images",
                        type: "array",
                        description: "Array of image data",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "file_path", type: "string", description: "File path of the image"),
                                new OA\Property(property: "caption", type: "string", description: "Image caption", nullable: true)
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Images created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/FeatureImage")),
                        new OA\Property(property: "count", type: "integer")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Bad request"),
            new OA\Response(response: 422, description: "Validation failed"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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
    #[OA\Get(
        path: "/api/gis/images/{id}/exists",
        summary: "Check if image file exists",
        description: "Check whether the physical file for a feature image still exists in storage",
        tags: ["Feature Images"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Image ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "File existence checked",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "exists", type: "boolean", example: true)
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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
    #[OA\Get(
        path: "/api/gis/images/{id}/url",
        summary: "Get image file URL",
        description: "Get the public URL of the image file in storage",
        tags: ["Feature Images"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Image ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "URL retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "url", type: "string", example: "http://localhost:8000/storage/feature-images/abc123.jpg")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Image not found"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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
    #[OA\Get(
        path: "/api/gis/images/statistics/all",
        summary: "Get image statistics",
        description: "Retrieve aggregate statistics for all feature images",
        tags: ["Feature Images"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Statistics retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "total_images", type: "integer"),
                                new OA\Property(property: "total_size", type: "integer"),
                                new OA\Property(property: "images_by_mime_type", type: "object")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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
    #[OA\Post(
        path: "/api/gis/images/cleanup",
        summary: "Cleanup orphaned image files",
        description: "Remove image files from storage that no longer have a corresponding database record",
        tags: ["Feature Images"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Cleanup completed",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "cleaned_files", type: "integer", example: 3)
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
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