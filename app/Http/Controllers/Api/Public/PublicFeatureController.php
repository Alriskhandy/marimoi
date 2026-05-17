<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Services\FeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PublicFeatureController extends Controller
{
    public function __construct(private FeatureService $featureService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $features = $this->featureService->getAllFeatures(
            $request->only(['layer_id', 'year', 'search']),
            min((int) $request->get('per_page', 50), 500)
        );

        return response()->json(['success' => true, 'data' => $features]);
    }

    public function show(int $id): JsonResponse
    {
        $feature = $this->featureService->getFeatureById($id);

        if (!$feature) {
            return response()->json(['success' => false, 'message' => 'Feature not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $feature]);
    }

    public function showByUuid(string $uuid): JsonResponse
    {
        $feature = $this->featureService->getFeatureByUuid($uuid);

        if (!$feature) {
            return response()->json(['success' => false, 'message' => 'Feature not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $feature]);
    }

    public function getByLayer(int $layerId): JsonResponse
    {
        $features = $this->featureService->getFeaturesByLayer($layerId);

        return response()->json(['success' => true, 'data' => $features]);
    }

    public function getGeoJson(int $layerId, Request $request): JsonResponse
    {
        $limit  = min((int) $request->get('limit', 500), 2000);
        $offset = (int) $request->get('offset', 0);

        $features = $this->featureService->getGeoJsonByLayer($layerId, $limit, $offset);
        $total    = $this->featureService->countGeoJsonByLayer($layerId);

        return response()->json([
            'success' => true,
            'data'    => $features,
            'meta'    => ['total' => $total, 'limit' => $limit, 'offset' => $offset],
        ]);
    }
}
