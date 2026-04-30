<?php

namespace App\Http\Controllers;

use App\Services\LayerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use OpenApi\Attributes as OA;

#[OA\Tag(name: "Layers", description: "Endpoint pengelolaan layer")]
#[OA\Schema(
    schema: "Layer",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Contoh Layer"),
        new OA\Property(property: "description", type: "string", example: "Deskripsi layer contoh", nullable: true),
        new OA\Property(property: "type", type: "string", enum: ["point", "line", "polygon"], example: "point"),
        new OA\Property(property: "style", type: "object", example: ["color" => "#FF0000", "weight" => 2]),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "parent_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "user_id", type: "integer", example: 1),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
        new OA\Property(property: "parent", ref: "#/components/schemas/Layer", nullable: true),
        new OA\Property(property: "children", type: "array", items: new OA\Items(ref: "#/components/schemas/Layer")),
        new OA\Property(property: "features_count", type: "integer", example: 10)
    ]
)]
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
    #[OA\Get(
        path: "/api/layers",
        summary: "Ambil daftar layer",
        description: "Mengambil daftar layer dengan paginasi dan filter opsional",
        tags: ["Layers"],
        parameters: [
            new OA\Parameter(
                name: "type",
                description: "Filter berdasarkan tipe layer",
                in: "query",
                schema: new OA\Schema(type: "string", enum: ["point", "line", "polygon"])
            ),
            new OA\Parameter(
                name: "user_id",
                description: "Filter berdasarkan ID pengguna",
                in: "query",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "is_active",
                description: "Filter berdasarkan status aktif",
                in: "query",
                schema: new OA\Schema(type: "boolean")
            ),
            new OA\Parameter(
                name: "parent_id",
                description: "Filter berdasarkan ID layer induk",
                in: "query",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "search",
                description: "Cari berdasarkan nama atau deskripsi layer",
                in: "query",
                schema: new OA\Schema(type: "string")
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
                description: "Data layer berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Layer")),
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
    #[OA\Post(
        path: "/api/layers",
        summary: "Buat layer baru",
        description: "Membuat layer baru dengan relasi induk opsional",
        tags: ["Layers"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "type"],
                properties: [
                    new OA\Property(property: "name", type: "string", description: "Nama layer"),
                    new OA\Property(property: "description", type: "string", description: "Deskripsi layer", nullable: true),
                    new OA\Property(property: "type", type: "string", enum: ["point", "line", "polygon"], description: "Tipe geometri layer"),
                    new OA\Property(property: "style", type: "object", description: "Konfigurasi tampilan layer"),
                    new OA\Property(property: "is_active", type: "boolean", description: "Status aktif layer", default: true),
                    new OA\Property(property: "parent_id", type: "integer", description: "ID layer induk untuk hierarki", nullable: true),
                    new OA\Property(property: "user_id", type: "integer", description: "ID pengguna pemilik layer")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Layer berhasil dibuat",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Layer")
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
    #[OA\Get(
        path: "/api/layers/{id}",
        summary: "Ambil layer berdasarkan ID",
        description: "Mengambil data layer tertentu berdasarkan ID-nya",
        tags: ["Layers"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID layer",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Data layer berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/Layer")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Layer tidak ditemukan"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Put(
        path: "/api/layers/{id}",
        summary: "Perbarui layer",
        description: "Memperbarui data layer yang sudah ada",
        tags: ["Layers"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID layer",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", description: "Nama layer"),
                    new OA\Property(property: "description", type: "string", description: "Deskripsi layer", nullable: true),
                    new OA\Property(property: "type", type: "string", enum: ["point", "line", "polygon"], description: "Tipe geometri layer"),
                    new OA\Property(property: "style", type: "object", description: "Konfigurasi tampilan layer"),
                    new OA\Property(property: "is_active", type: "boolean", description: "Status aktif layer"),
                    new OA\Property(property: "parent_id", type: "integer", description: "ID layer induk untuk hierarki", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Layer berhasil diperbarui",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Layer")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Layer tidak ditemukan"),
            new OA\Response(response: 422, description: "Validasi gagal"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Delete(
        path: "/api/layers/{id}",
        summary: "Hapus layer",
        description: "Menghapus layer (hanya jika tidak memiliki anak atau fitur)",
        tags: ["Layers"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID layer",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Layer berhasil dihapus",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Layer tidak dapat dihapus (memiliki anak atau fitur)"),
            new OA\Response(response: 404, description: "Layer tidak ditemukan"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Get(
        path: "/api/layers/roots",
        summary: "Ambil layer root",
        description: "Mengambil semua layer yang tidak memiliki induk (layer level paling atas)",
        tags: ["Layers"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Data layer root berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Layer"))
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Get(
        path: "/api/layers/{parentId}/children",
        summary: "Ambil layer anak",
        description: "Mengambil semua layer anak langsung dari layer tertentu",
        tags: ["Layers"],
        parameters: [
            new OA\Parameter(
                name: "parentId",
                description: "ID layer induk",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Data layer anak berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Layer"))
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Layer induk tidak ditemukan"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Get(
        path: "/api/layers/tree",
        summary: "Ambil pohon hierarki layer",
        description: "Mengambil seluruh struktur hierarki layer secara lengkap",
        tags: ["Layers"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Pohon hierarki layer berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Layer"))
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Get(
        path: "/api/layers/user/{userId}",
        summary: "Ambil layer berdasarkan pengguna",
        description: "Mengambil semua layer yang dibuat oleh pengguna tertentu",
        tags: ["Layers"],
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
                description: "Data layer berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Layer"))
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
    #[OA\Get(
        path: "/api/layers/active/all",
        summary: "Ambil layer aktif",
        description: "Mengambil semua layer yang sedang aktif",
        tags: ["Layers"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Data layer aktif berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Layer"))
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Post(
        path: "/api/layers/{id}/toggle-active",
        summary: "Ubah status aktif layer",
        description: "Mengubah status aktif/nonaktif sebuah layer",
        tags: ["Layers"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID layer",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Status layer berhasil diubah",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Layer")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Layer tidak ditemukan"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Get(
        path: "/api/layers/type/{type}",
        summary: "Ambil layer berdasarkan tipe",
        description: "Mengambil semua layer dengan tipe geometri tertentu",
        tags: ["Layers"],
        parameters: [
            new OA\Parameter(
                name: "type",
                description: "Tipe geometri layer",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", enum: ["point", "line", "polygon"])
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Data layer berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Layer"))
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Post(
        path: "/api/layers/{id}/move",
        summary: "Pindahkan layer ke induk baru",
        description: "Memindahkan layer ke induk baru dalam hierarki (mencegah referensi melingkar)",
        tags: ["Layers"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID layer yang akan dipindahkan",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "parent_id", type: "integer", description: "ID layer induk baru (null untuk level root)", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Layer berhasil dipindahkan",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Layer")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Terdeteksi referensi melingkar atau induk tidak valid"),
            new OA\Response(response: 404, description: "Layer tidak ditemukan"),
            new OA\Response(response: 422, description: "Validasi gagal"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
    #[OA\Get(
        path: "/api/layers/{id}/with-features",
        summary: "Ambil layer beserta jumlah fitur",
        description: "Mengambil data layer beserta jumlah fitur yang dimilikinya",
        tags: ["Layers"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID layer",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Data layer beserta jumlah fitur berhasil diambil",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "layer", ref: "#/components/schemas/Layer"),
                            new OA\Property(property: "features_count", type: "integer", example: 25)
                        ])
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Layer tidak ditemukan"),
            new OA\Response(response: 500, description: "Kesalahan server internal")
        ]
    )]
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
