<?php
// app/Http/Controllers/VisitorController.php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VisitorController extends Controller
{
    public function index()
    {
        $visitors = Visitor::with([])
            ->latest()
            ->paginate(20);

        return view('visitors.index', compact('visitors'));
    }

    public function dashboard()
    {
        $stats = [
            'total_visitors' => Visitor::count(),
            'today_visitors' => Visitor::today()->count(),
            'unique_visitors' => Visitor::unique()->count(),
            'with_location' => Visitor::whereNotNull('latitude')->count(),
        ];

        // Top halaman yang dikunjungi
        $topPages = Visitor::select('page_visited', DB::raw('count(*) as visits'))
            ->groupBy('page_visited')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        // Top negara
        $topCountries = Visitor::select('country', DB::raw('count(*) as visits'))
            ->whereNotNull('country')
            ->where('country', '!=', 'Local')
            ->groupBy('country')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        // Statistik 7 hari terakhir
        $weeklyStats = Visitor::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as visits'),
                DB::raw('count(distinct ip) as unique_visits')
            )
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('visitors.dashboard', compact(
            'stats', 
            'topPages', 
            'topCountries', 
            'weeklyStats'
        ));
    }

    public function mapData()
    {
        $visitors = Visitor::select('latitude', 'longitude', 'city', 'country', 'created_at')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest()
            ->limit(500)
            ->get()
            ->map(function ($visitor) {
                return [
                    'lat' => (float) $visitor->latitude,
                    'lng' => (float) $visitor->longitude,
                    'city' => $visitor->city,
                    'country' => $visitor->country,
                    'date' => $visitor->created_at->format('d/m/Y H:i')
                ];
            });

        return response()->json($visitors);
    }
}