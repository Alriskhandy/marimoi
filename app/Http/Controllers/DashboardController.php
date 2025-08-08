<?php

namespace App\Http\Controllers;

use App\Models\DataSpatial;
use App\Models\Opd;
use App\Models\Aspirasi;
use App\Models\KategoriAspirasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard
     */
    public function index()
    {
        $totalLokasi = DataSpatial::count();
        $totalOpd = Opd::count();
        $totalPendingAspirasi = Aspirasi::where('status', 'pending')->count();
        $totalAspirasi = Aspirasi::count();
        $totalSelesaiAspirasi = Aspirasi::where('status', 'selesai')->count();

        // Get monthly data for current year
        $currentYear = date('Y');
        $monthlyAspirasi = $this->getMonthlyAspirasiData($currentYear);
        
        // Get category distribution
        $categoryData = $this->getCategoryDistributionForDashboard();
        
        // Get recent aspirasi
        $recentAspirasi = Aspirasi::with('kategori')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalLokasi',
            'totalOpd',
            'totalPendingAspirasi',
            'totalAspirasi',
            'totalSelesaiAspirasi',
            'monthlyAspirasi',
            'categoryData',
            'recentAspirasi'
        ));
    }

    /**
     * Get available years from aspirasi data
     */
    public function getAvailableYears()
    {
        $years = DB::table('aspirasi')
            ->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        return response()->json([
            'success' => true,
            'years' => $years
        ]);
    }

    /**
     * Get kategori aspirasi for dropdown
     */
    public function getCategories()
    {
        $categories = DB::table('kategori_aspirasi')
            ->select('id', 'nama')
            ->orderBy('nama', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Get comprehensive statistics for dashboard
     */
    public function getStatistics(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $categoryId = $request->get('category');

        try {
            // Build base query
            $baseQuery = DB::table('aspirasi')
                ->whereYear('created_at', $year);

            if ($categoryId) {
                $baseQuery->where('kategori_aspirasi_id', $categoryId);
            }

            // Get basic statistics
            $stats = $this->getBasicStats($baseQuery, $year, $categoryId);

            // Get monthly data
            $monthly = $this->getMonthlyData($baseQuery);

            // Get category distribution
            $categories = $this->getCategoryDistribution($year, $categoryId);

            // Get status by category
            $statusByCategory = $this->getStatusByCategory($year, $categoryId);

            // Get jenis distribution
            $jenis = $this->getJenisDistribution($baseQuery);

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'monthly' => $monthly,
                'categories' => $categories,
                'statusByCategory' => $statusByCategory,
                'jenis' => $jenis
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get basic statistics with month-over-month comparison
     */
    private function getBasicStats($baseQuery, $year, $categoryId)
    {
        $currentMonth = date('n');
        $currentYear = date('Y');

        // Current period stats
        $total = (clone $baseQuery)->count();
        $pending = (clone $baseQuery)->where('status', 'pending')->count();
        $diproses = (clone $baseQuery)->whereIn('status', ['diproses', 'ditindaklanjuti'])->count();
        $selesai = (clone $baseQuery)->where('status', 'selesai')->count();

        // Previous month comparison (if current year)
        $changes = ['totalChange' => 0, 'pendingChange' => 0, 'prosesChange' => 0, 'selesaiChange' => 0];
        
        if ($year == $currentYear && $currentMonth > 1) {
            $prevMonthQuery = DB::table('aspirasi')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $currentMonth - 1);

            if ($categoryId) {
                $prevMonthQuery->where('kategori_aspirasi_id', $categoryId);
            }

            $prevTotal = $prevMonthQuery->count();
            $prevPending = (clone $prevMonthQuery)->where('status', 'pending')->count();
            $prevDiproses = (clone $prevMonthQuery)->whereIn('status', ['diproses', 'ditindaklanjuti'])->count();
            $prevSelesai = (clone $prevMonthQuery)->where('status', 'selesai')->count();

            // Calculate percentage changes
            $changes['totalChange'] = $prevTotal > 0 ? round((($total - $prevTotal) / $prevTotal) * 100, 1) : 0;
            $changes['pendingChange'] = $prevPending > 0 ? round((($pending - $prevPending) / $prevPending) * 100, 1) : 0;
            $changes['prosesChange'] = $prevDiproses > 0 ? round((($diproses - $prevDiproses) / $prevDiproses) * 100, 1) : 0;
            $changes['selesaiChange'] = $prevSelesai > 0 ? round((($selesai - $prevSelesai) / $prevSelesai) * 100, 1) : 0;
        }

        return array_merge([
            'total' => $total,
            'pending' => $pending,
            'diproses' => $diproses,
            'selesai' => $selesai
        ], $changes);
    }

    /**
     * Get monthly data for trend chart
     */
    private function getMonthlyData($baseQuery)
    {
        $monthlyData = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $total = (clone $baseQuery)
                ->whereMonth('created_at', $month)
                ->count();
                
            $selesai = (clone $baseQuery)
                ->whereMonth('created_at', $month)
                ->where('status', 'selesai')
                ->count();

            $monthlyData['total'][] = $total;
            $monthlyData['selesai'][] = $selesai;
        }

        return $monthlyData;
    }

    /**
     * Get monthly aspirasi data for dashboard main page
     */
    private function getMonthlyAspirasiData($year)
    {
        $monthlyData = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $total = DB::table('aspirasi')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();
                
            $selesai = DB::table('aspirasi')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->where('status', 'selesai')
                ->count();

            $monthlyData['total'][] = $total;
            $monthlyData['selesai'][] = $selesai;
        }

        return $monthlyData;
    }

    /**
     * Get category distribution for doughnut chart
     */
    private function getCategoryDistribution($year, $categoryId = null)
    {
        $query = DB::table('aspirasi')
            ->join('kategori_aspirasi', 'aspirasi.kategori_aspirasi_id', '=', 'kategori_aspirasi.id')
            ->select('kategori_aspirasi.nama_kategori', DB::raw('COUNT(*) as total'))
            ->whereYear('aspirasi.created_at', $year)
            ->groupBy('kategori_aspirasi.id', 'kategori_aspirasi.nama_kategori')
            ->orderBy('total', 'desc');

        if ($categoryId) {
            $query->where('aspirasi.kategori_aspirasi_id', $categoryId);
        }

        $results = $query->get();

        return [
            'labels' => $results->pluck('nama')->toArray(),
            'values' => $results->pluck('total')->toArray()
        ];
    }

    /**
     * Get category distribution for main dashboard
     */
    private function getCategoryDistributionForDashboard()
    {
        $currentYear = date('Y');
        
        $results = DB::table('aspirasi')
            ->join('kategori_aspirasi', 'aspirasi.kategori_aspirasi_id', '=', 'kategori_aspirasi.id')
            ->select('kategori_aspirasi.nama_kategori', DB::raw('COUNT(*) as total'))
            ->whereYear('aspirasi.created_at', $currentYear)
            ->groupBy('kategori_aspirasi.id', 'kategori_aspirasi.nama_kategori')
            ->orderBy('total', 'desc')
            ->limit(6) // Limit untuk dashboard utama
            ->get();

        return [
            'labels' => $results->pluck('nama')->toArray(),
            'values' => $results->pluck('total')->toArray()
        ];
    }

    /**
     * Get status distribution by category
     */
    private function getStatusByCategory($year, $categoryId = null)
    {
        $query = DB::table('aspirasi')
            ->join('kategori_aspirasi', 'aspirasi.kategori_aspirasi_id', '=', 'kategori_aspirasi.id')
            ->select(
                'kategori_aspirasi.nama_kategori as category',
                DB::raw('SUM(CASE WHEN aspirasi.status = "pending" THEN 1 ELSE 0 END) as pending'),
                DB::raw('SUM(CASE WHEN aspirasi.status IN ("diproses", "ditindaklanjuti") THEN 1 ELSE 0 END) as diproses'),
                DB::raw('SUM(CASE WHEN aspirasi.status = "selesai" THEN 1 ELSE 0 END) as selesai')
            )
            ->whereYear('aspirasi.created_at', $year)
            ->groupBy('kategori_aspirasi.id', 'kategori_aspirasi.nama_kategori')
            ->orderBy('kategori_aspirasi.nama_kategori');

        if ($categoryId) {
            $query->where('aspirasi.kategori_aspirasi_id', $categoryId);
        }

        $results = $query->get();

        return [
            'categories' => $results->pluck('category')->toArray(),
            'pending' => $results->pluck('pending')->toArray(),
            'diproses' => $results->pluck('diproses')->toArray(),
            'selesai' => $results->pluck('selesai')->toArray()
        ];
    }

    /**
     * Get jenis aspirasi distribution
     */
    private function getJenisDistribution($baseQuery)
    {
        $results = (clone $baseQuery)
            ->select('jenis_aspirasi', DB::raw('COUNT(*) as total'))
            ->groupBy('jenis_aspirasi')
            ->orderBy('total', 'desc')
            ->get();

        return [
            'labels' => $results->pluck('jenis_aspirasi')->map(function($jenis) {
                return ucfirst($jenis);
            })->toArray(),
            'values' => $results->pluck('total')->toArray()
        ];
    }

    /**
     * Get top performing categories
     */
    public function getTopCategories(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $limit = $request->get('limit', 5);

        $topCategories = DB::table('aspirasi')
            ->join('kategori_aspirasi', 'aspirasi.kategori_aspirasi_id', '=', 'kategori_aspirasi.id')
            ->select(
                'kategori_aspirasi.nama_kategori',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "selesai" THEN 1 ELSE 0 END) as selesai'),
                DB::raw('ROUND((SUM(CASE WHEN status = "selesai" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as completion_rate')
            )
            ->whereYear('aspirasi.created_at', $year)
            ->groupBy('kategori_aspirasi.id', 'kategori_aspirasi.nama_kategori')
            ->orderBy('total', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $topCategories
        ]);
    }

    /**
     * Get response time analytics
     */
    public function getResponseTimeAnalytics(Request $request)
    {
        $year = $request->get('year', date('Y'));

        $responseTime = DB::table('aspirasi')
            ->select(
                DB::raw('AVG(DATEDIFF(tanggal_respon, created_at)) as avg_response_days'),
                DB::raw('MIN(DATEDIFF(tanggal_respon, created_at)) as min_response_days'),
                DB::raw('MAX(DATEDIFF(tanggal_respon, created_at)) as max_response_days'),
                DB::raw('COUNT(CASE WHEN tanggal_respon IS NOT NULL THEN 1 END) as responded_count'),
                DB::raw('COUNT(*) as total_count')
            )
            ->whereYear('created_at', $year)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $responseTime
        ]);
    }

    /**
     * Get dashboard statistics for widgets
     */
    public function getDashboardStats()
    {
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        // Total counts
        $totalAspirasi = Aspirasi::whereYear('created_at', $currentYear)->count();
        $totalPending = Aspirasi::where('status', 'pending')->whereYear('created_at', $currentYear)->count();
        $totalSelesai = Aspirasi::where('status', 'selesai')->whereYear('created_at', $currentYear)->count();
        $totalOpd = Opd::count();
        $totalLokasi = DataSpatial::count();
        
        // This month vs last month
        $thisMonth = Aspirasi::whereYear('created_at', $currentYear)
                            ->whereMonth('created_at', $currentMonth)
                            ->count();
                            
        $lastMonth = $currentMonth > 1 ? 
            Aspirasi::whereYear('created_at', $currentYear)
                   ->whereMonth('created_at', $currentMonth - 1)
                   ->count() : 0;
        
        $monthlyChange = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;
        
        // Response rate
        $totalWithResponse = Aspirasi::whereNotNull('tanggal_respon')
                                   ->whereYear('created_at', $currentYear)
                                   ->count();
        $responseRate = $totalAspirasi > 0 ? round(($totalWithResponse / $totalAspirasi) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'totalAspirasi' => $totalAspirasi,
                'totalPending' => $totalPending,
                'totalSelesai' => $totalSelesai,
                'totalOpd' => $totalOpd,
                'totalLokasi' => $totalLokasi,
                'monthlyChange' => $monthlyChange,
                'responseRate' => $responseRate,
                'thisMonth' => $thisMonth,
                'lastMonth' => $lastMonth
            ]
        ]);
    }

    /**
     * Show detailed statistics page
     */
    public function statistics()
    {
        $currentYear = date('Y');
        
        // Get available years for filter
        $availableYears = DB::table('aspirasi')
            ->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Get categories for filter
        $categories = DB::table('kategori_aspirasi')
            ->select('id', 'nama')
            ->orderBy('nama')
            ->get();

        return view('backend.pages.aspirasi.statistics', compact('currentYear', 'availableYears', 'categories'));
    }
}