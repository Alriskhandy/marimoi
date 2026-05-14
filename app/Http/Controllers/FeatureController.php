<?php

namespace App\Http\Controllers;

use App\Services\FeatureService;
use App\Services\FeatureImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

use OpenApi\Attributes as OA;

#[OA\Tag(name: "Features", description: "Endpoint pengelolaan fitur")]
#[OA\Schema(
    schema: "Feature",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "uuid", type: "string", example: "550e8400-e29b-41d4-a716-446655440000"),
        new OA\Property(property: "layer_id", type: "integer", example: 1),
        new OA\Property(property: "user_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "properties", type: "object", example: ["name" => "Contoh Fitur", "description" => "Deskripsi fitur contoh"]),
        new OA\Property(property: "year", type: "integer", example: 2024, nullable: true),
        new OA\Property(property: "views", type: "integer", example: 0),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
        new OA\Property(property: "layer", ref: "#/components/schemas/Layer", nullable: true),
        new OA\Property(property: "user", ref: "#/components/schemas/User", nullable: true),
        new OA\Property(property: "images", type: "array", items: new OA\Items(ref: "#/components/schemas/FeatureImage"))
    ]
)]
#[OA\Schema(
    schema: "PaginationMeta",
    type: "object",
    properties: [
        new OA\Property(property: "current_page", type: "integer", example: 1),
        new OA\Property(property: "per_page", type: "integer", example: 50),
        new OA\Property(property: "total", type: "integer", example: 100),
        new OA\Property(property: "last_page", type: "integer", example: 2),
        new OA\Property(property: "from", type: "integer", example: 1),
        new OA\Property(property: "to", type: "integer", example: 50)
    ]
)]
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
    #[OA\Get(
        path: "/api/features",
        summary: "Ambil daftar fitur",
        description: "Mengambil daftar fitur dengan paginasi dan filter opsional",
        tags: ["Features"],
        parameters: [
            new OA\Parameter(
                name: "layer_id",
                description: "Filter berdasarkan ID layer",
                in: "query",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "user_id",
                description: "Filter berdasarkan ID pengguna",
                in: "query",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "year",
                description: "Filter berdasarkan tahun",
                in: "query",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "search",
                description: "Cari berdasarkan properti atau UUID",
                in: "query",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "layer_type",
                description: "Filter berdasarkan tipe layer (point, line, polygon)",
                in: "query",
                schema: new OA\Schema(type: "string", enum: ["point", "line", "polygon"])
            ),
            new OA\Parameter(
                name: "per_page",
                description: "Jumlah data per halaman (maks 500)",
                in: "query",
                schema: new OA\Schema(type: "integer", default: 50, maximum: 500)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Data fitur berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Feature")),
                        new OA\Property(property: "meta", ref: "#/components/schemas/PaginationMeta")
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Post(
        path: "/api/features",
        summary: "Buat fitur baru",
        description: "Membuat fitur baru dengan gambar opsional",
        tags: ["Features"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["layer_id"],
                properties: [
                    new OA\Property(property: "layer_id", type: "integer", description: "ID layer"),
                    new OA\Property(property: "user_id", type: "integer", description: "ID pengguna", nullable: true),
                    new OA\Property(property: "properties", type: "object", description: "Properti fitur"),
                    new OA\Property(property: "year", type: "integer", description: "Tahun", nullable: true),
                    new OA\Property(property: "uuid", type: "string", description: "UUID (dibuat otomatis jika kosong)", nullable: true),
                    new OA\Property(property: "images", type: "array", description: "Gambar fitur", items: new OA\Items(
                        properties: [
                            new OA\Property(property: "file_path", type: "string"),
                            new OA\Property(property: "caption", type: "string", nullable: true)
                        ]
                    ), nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Fitur berhasil dibuat",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Feature")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validasi gagal"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Get(
        path: "/api/features/{id}",
        summary: "Ambil fitur berdasarkan ID",
        description: "Mengambil data fitur tertentu berdasarkan ID-nya",
        tags: ["Features"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID fitur",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Data fitur berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/Feature")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Fitur tidak ditemukan"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Put(
        path: "/api/features/{id}",
        summary: "Perbarui fitur",
        description: "Memperbarui data fitur yang sudah ada",
        tags: ["Features"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID fitur",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "layer_id", type: "integer", description: "ID layer"),
                    new OA\Property(property: "user_id", type: "integer", description: "ID pengguna", nullable: true),
                    new OA\Property(property: "properties", type: "object", description: "Properti fitur"),
                    new OA\Property(property: "year", type: "integer", description: "Tahun", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Fitur berhasil diperbarui",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Feature")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Fitur tidak ditemukan"),
            new OA\Response(response: 422, description: "Validasi gagal"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Delete(
        path: "/api/features/{id}",
        summary: "Hapus fitur",
        description: "Menghapus fitur beserta gambar-gambar yang terkait",
        tags: ["Features"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID fitur",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Fitur berhasil dihapus",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Fitur tidak ditemukan"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Get(
        path: "/api/features/uuid/{uuid}",
        summary: "Ambil fitur berdasarkan UUID",
        description: "Mengambil data fitur tertentu berdasarkan UUID-nya",
        tags: ["Features"],
        parameters: [
            new OA\Parameter(
                name: "uuid",
                description: "UUID fitur",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Data fitur berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/Feature")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Fitur tidak ditemukan"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Get(
        path: "/api/features/by-layer/{layerId}",
        summary: "Ambil fitur berdasarkan layer",
        description: "Mengambil semua fitur yang termasuk dalam layer tertentu",
        tags: ["Features"],
        parameters: [
            new OA\Parameter(
                name: "layerId",
                description: "ID layer",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Data fitur berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Feature"))
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Layer tidak ditemukan"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
     * Get features as GeoJSON FeatureCollection for a specific layer (paginated).
     */
    public function getGeoJsonByLayer(int $layerId, Request $request): JsonResponse
    {
        try {
            $limit  = min((int) $request->get('limit', 500), 3000);
            $offset = max(0, (int) $request->get('offset', 0));

            $rows  = $this->featureService->getGeoJsonByLayer($layerId, $limit, $offset);
            $total = $this->featureService->countGeoJsonByLayer($layerId);

            $features = $rows->map(function ($row) {
                $geometry   = $row->geojson ? json_decode($row->geojson) : null;
                $properties = is_string($row->properties)
                    ? json_decode($row->properties, true)
                    : (array) $row->properties;

                // Inject layer name as kategori for popup display
                $properties['kategori'] = $row->layer_name;
                $properties['uuid']     = $row->uuid;

                return [
                    'type'       => 'Feature',
                    'geometry'   => $geometry,
                    'properties' => $properties,
                ];
            })->filter(fn($f) => $f['geometry'] !== null)->values();

            return response()->json([
                'type'     => 'FeatureCollection',
                'features' => $features,
                'meta'     => [
                    'total'    => $total,
                    'limit'    => $limit,
                    'offset'   => $offset,
                    'has_more' => ($offset + $features->count()) < $total,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve GeoJSON', 500, $e->getMessage());
        }
    }

    /**
     * Get features by user
     */
    #[OA\Get(
        path: "/api/features/by-user/{userId}",
        summary: "Ambil fitur berdasarkan pengguna",
        description: "Mengambil semua fitur yang dibuat oleh pengguna tertentu",
        tags: ["Features"],
        parameters: [
            new OA\Parameter(
                name: "userId",
                description: "ID pengguna",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Data fitur berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Feature"))
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Pengguna tidak ditemukan"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Get(
        path: "/api/features/within-bounds",
        summary: "Ambil fitur dalam batas geografis",
        description: "Mengambil fitur yang berada dalam batas geografis yang ditentukan",
        tags: ["Features"],
        parameters: [
            new OA\Parameter(
                name: "north",
                description: "Lintang utara",
                in: "query",
                required: true,
                schema: new OA\Schema(type: "number", format: "float")
            ),
            new OA\Parameter(
                name: "south",
                description: "Lintang selatan",
                in: "query",
                required: true,
                schema: new OA\Schema(type: "number", format: "float")
            ),
            new OA\Parameter(
                name: "east",
                description: "Bujur timur",
                in: "query",
                required: true,
                schema: new OA\Schema(type: "number", format: "float")
            ),
            new OA\Parameter(
                name: "west",
                description: "Bujur barat",
                in: "query",
                required: true,
                schema: new OA\Schema(type: "number", format: "float")
            ),
            new OA\Parameter(
                name: "layer_id",
                description: "Filter berdasarkan ID layer",
                in: "query",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "layer_type",
                description: "Filter berdasarkan tipe layer",
                in: "query",
                schema: new OA\Schema(type: "string", enum: ["point", "line", "polygon"])
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Data fitur berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Feature"))
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Parameter batas tidak valid"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Post(
        path: "/api/features/bulk",
        summary: "Buat banyak fitur sekaligus",
        description: "Membuat banyak fitur dalam satu permintaan",
        tags: ["Features"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["features"],
                properties: [
                    new OA\Property(
                        property: "features",
                        type: "array",
                        description: "Array fitur yang akan dibuat",
                        items: new OA\Items(
                            required: ["layer_id"],
                            properties: [
                                new OA\Property(property: "layer_id", type: "integer"),
                                new OA\Property(property: "user_id", type: "integer", nullable: true),
                                new OA\Property(property: "properties", type: "object"),
                                new OA\Property(property: "year", type: "integer", nullable: true),
                                new OA\Property(property: "uuid", type: "string", nullable: true)
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Fitur berhasil dibuat",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Feature")),
                        new OA\Property(property: "count", type: "integer")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validasi gagal"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
