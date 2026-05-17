<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Aspirasi;
use App\Models\Visitor;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PublicStatisticController extends Controller
{

    /**
     * Display the count of visitors.
     */

    #[OA\Get(
        path: '/api/v1/count-visitors',
        tags: ['Statistics'],
        summary: 'Get visitor counts',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function showCountVisitors(): JsonResponse
    {
        // Real visitor counts using Visitor model scopes
        $visitorsToday = Visitor::today()->count();
        $visitorsWeek = Visitor::thisWeek()->count();
        $visitorsMonth = Visitor::thisMonth()->count();
        $visitorsTotal = Visitor::count();

        $countVisitors = [
            'today' => $visitorsToday,
            'week'  => $visitorsWeek,
            'month' => $visitorsMonth,
            'total' => $visitorsTotal,
        ];

        return response()->json([
            'success' => true,
            'data'    => $countVisitors,
        ]);
    }

    /**
     * Display the count of aspirations.
     */
    #[OA\Get(
        path: '/api/v1/count-aspirations',
        tags: ['Statistics'],
        summary: 'Get aspiration counts',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function showCountAspirations(): JsonResponse
    {
        $totalKritik = Aspirasi::where('jenis_aspirasi', 'kritik & saran')->count();
        $totalUsulan = Aspirasi::where('jenis_aspirasi', 'usulan')->count();

        $countAspirations = [
            'kritik_dan_saran' => $totalKritik,
            'usulan' => $totalUsulan,
        ];

        return response()->json([
            'success' => true,
            'data'    => $countAspirations,
        ]);
    }
}
