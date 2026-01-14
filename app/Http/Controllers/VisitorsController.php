<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use App\Exports\VisitorExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class VisitorsController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameters
        $period = $request->get('period', 'today');
        $country = $request->get('country');
        $city = $request->get('city');
        $page = $request->get('page_filter');
        $search = $request->get('search');

        // Base query
        $query = Visitor::query();

        // Apply period filter
        switch ($period) {
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
            case 'year':
                $query->whereYear('created_at', Carbon::now()->year);
                break;
            default:
                $query->today();
        }

        // Apply additional filters
        if ($country) {
            $query->where('country', $country);
        }

        if ($city) {
            $query->where('city', $city);
        }

        if ($page) {
            $query->where('page_visited', 'like', "%{$page}%");
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('ip', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('page_visited', 'like', "%{$search}%");
            });
        }

        // Get visitors with pagination
        $visitors = $query->latest()->paginate(25);

        // Get statistics
        $stats = $this->getStats($period);

        // Get analytics data
        $analytics = $this->getAnalytics($period);

        // Get filter options
        $countries = Visitor::select('country')
                           ->whereNotNull('country')
                           ->distinct()
                           ->orderBy('country')
                           ->pluck('country');

        $cities = Visitor::select('city')
                        ->whereNotNull('city')
                        ->distinct()
                        ->orderBy('city')
                        ->pluck('city');

        return view('backend.pages.visitors.index', compact(
            'visitors', 'stats', 'analytics', 'countries', 'cities', 'period'
        ));
    }

    public function show(Visitor $visitor)
    {
        // Get visitor details with related visits
        $relatedVisits = Visitor::where('ip', $visitor->ip)
                               ->where('id', '!=', $visitor->id)
                               ->latest()
                               ->limit(10)
                               ->get();

        // Get visitor session data
        $sessionData = $this->getVisitorSessionData($visitor->ip);

        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $visitor,
                'related_visits' => $relatedVisits,
                'session_data' => $sessionData
            ]);
        }

        return view('backend.pages.visitors.show', compact(
            'visitor', 'relatedVisits', 'sessionData'
        ));
    }

    public function destroy(Visitor $visitor)
    {
        $visitor->delete();

        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data visitor berhasil dihapus.'
            ]);
        }

        return redirect()->route('visitors.index')
                        ->with('success', 'Data visitor berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        try {
            // Check if it's a date-based cleanup or selected IDs
            if ($request->has('days')) {
                $days = (int) $request->input('days');
                $count = Visitor::where('created_at', '<', Carbon::now()->subDays($days))->delete();
                
                return response()->json([
                    'status' => 'success',
                    'message' => "Berhasil menghapus {$count} data pengunjung yang lebih dari {$days} hari"
                ]);
            }

            // Standard bulk delete with selected IDs
            $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|integer|exists:visitors,id'
            ]);

            $count = Visitor::whereIn('id', $request->ids)->delete();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Berhasil menghapus {$count} data pengunjung"
                ]);
            }

            return redirect()->back()->with('success', "Berhasil menghapus {$count} data pengunjung");

        } catch (\Exception $e) {
            Log::error('Bulk delete visitors error: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan saat menghapus data'
                ], 500);
            }

            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data');
        }
    }

    public function export(Request $request)
    {
        try {
            $period = $request->get('period', 'today');
            $format = $request->get('format', 'xlsx');
            
            $fileName = 'visitors_' . $period . '_' . Carbon::now()->format('Y-m-d_H-i-s');
            
            $export = new VisitorExport($period);
            
            if ($format === 'csv') {
                return $export->download($fileName . '.csv', \Maatwebsite\Excel\Excel::CSV);
            }
            
            return $export->download($fileName . '.xlsx');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengekspor data: ' . $e->getMessage());
        }
    }

    public function analytics(Request $request)
    {
        $period = $request->get('period', 'week');
        
        $data = [
            'daily_visits' => $this->getDailyVisits($period),
            'top_pages' => Visitor::getTopPages($period),
            'top_countries' => Visitor::getTopCountries($period),
            'browser_stats' => Visitor::getBrowserStats($period),
            'device_stats' => $this->getDeviceStats($period),
            'hourly_visits' => $this->getHourlyVisits($period)
        ];

        return response()->json($data);
    }

    public function dashboard()
    {
        $stats = [
            'total_visitors' => Visitor::count(),
            'today_visitors' => Visitor::today()->count(),
            'unique_visitors' => Visitor::distinct('ip')->count(),
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

    // Private helper methods
    private function getStats($period)
    {
        switch ($period) {
            case 'week':
                return Visitor::getWeeklyStats();
            case 'month':
                return Visitor::getMonthlyStats();
            case 'year':
                return $this->getYearlyStats();
            default:
                return Visitor::getTodayStats();
        }
    }

    private function getAnalytics($period)
    {
        return [
            'top_pages' => Visitor::getTopPages($period, 5),
            'top_countries' => Visitor::getTopCountries($period, 5),
            'browser_stats' => Visitor::getBrowserStats($period),
            'daily_visits' => $this->getDailyVisits($period)
        ];
    }

    private function getDailyVisits($period)
    {
        $days = $period === 'month' ? 30 : ($period === 'week' ? 7 : 1);
        
        return Visitor::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as visits'),
                    DB::raw('COUNT(DISTINCT ip) as unique_visitors')
                )
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
    }

    private function getHourlyVisits($period)
    {
        $query = Visitor::select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('COUNT(*) as visits')
        );

        switch ($period) {
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
            default:
                $query->today();
        }

        return $query->groupBy('hour')
                    ->orderBy('hour')
                    ->get();
    }

    private function getDeviceStats($period)
    {
        $query = Visitor::query();
        
        switch ($period) {
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
            default:
                $query->today();
        }

        $visitors = $query->get();
        return $visitors->groupBy('device_type')->map->count();
    }

    private function getVisitorSessionData($ip)
    {
        $visits = Visitor::where('ip', $ip)
                        ->orderBy('created_at')
                        ->get();

        return [
            'total_visits' => $visits->count(),
            'first_visit' => $visits->first()?->created_at,
            'last_visit' => $visits->last()?->created_at,
            'total_pages' => $visits->unique('page_visited')->count(),
            'session_duration' => $this->calculateSessionDuration($visits),
            'pages_visited' => $visits->pluck('page_visited')->unique()->values()
        ];
    }

    private function calculateSessionDuration($visits)
    {
        if ($visits->count() < 2) return 0;
        
        $first = $visits->first()->created_at;
        $last = $visits->last()->created_at;
        
        return $first->diffInMinutes($last);
    }

    private function getYearlyStats()
    {
        return [
            'total_visits' => Visitor::whereYear('created_at', Carbon::now()->year)->count(),
            'unique_visitors' => Visitor::whereYear('created_at', Carbon::now()->year)->distinct('ip')->count(),
            'page_views' => Visitor::whereYear('created_at', Carbon::now()->year)->count(),
            'bounce_rate' => $this->calculateBounceRate('year')
        ];
    }

    private function calculateBounceRate($period)
    {
        $query = Visitor::query();
        
        switch ($period) {
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
            case 'year':
                $query->whereYear('created_at', Carbon::now()->year);
                break;
            default:
                $query->today();
        }

        $totalSessions = $query->distinct('ip')->count();
        
        if ($totalSessions == 0) return 0;

        $singlePageSessions = $query->select('ip')
                                  ->groupBy('ip')
                                  ->havingRaw('count(*) = 1')
                                  ->get()
                                  ->count();

        return round(($singlePageSessions / $totalSessions) * 100, 2);
    }
}