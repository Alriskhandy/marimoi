<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Layer;
use App\Models\ProjectFeedback;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class AdminDashboardController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/dashboard/statistics',
        tags: ['Admin Dashboard'],
        summary: 'Statistik ringkas',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function statistics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'users'     => User::count(),
                'features'  => Feature::count(),
                'layers'    => Layer::count(),
                'feedbacks' => [
                    'total'    => ProjectFeedback::count(),
                    'pending'  => ProjectFeedback::where('status', 'pending')->count(),
                    'selesai'  => ProjectFeedback::where('status', 'selesai')->count(),
                ],
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/dashboard/visitors',
        tags: ['Admin Dashboard'],
        summary: 'Statistik visitor per hari',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'days', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 30))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function getVisitorStatistics(Request $request): JsonResponse
    {
        $days  = min((int) $request->get('days', 30), 365);
        $stats = DB::table('visitors')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $stats]);
    }
}
