<?php

namespace App\Http\Controllers;

use App\Models\DataSpatial;
use App\Models\Opd;
use App\Models\Aspirasi;
use App\Models\KategoriAspirasi;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
   

   private function isAdminOpd()
    {
        return Auth::user()->role->slug === 'admin-opd';
    }
    /**
     * Apply OPD filter to raw DB query
     */
    private function applyOpdFilterToRawQuery($query)
    {
        if ($this->isAdminOpd()) {
            $userOpdId = Auth::user()->opd_id;
            return $query->join('kategori_aspirasi', 'aspirasi.kategori_aspirasi_id', '=', 'kategori_aspirasi.id')
                        ->where('kategori_aspirasi.opd_id', $userOpdId);
        }
        return $query;
    }

    // ... existing methods remain the same ...

    /**
     * Display the main dashboard
     */
    public function index()
    {
        if ($this->isAdminOpd()) {
            $userOpdId = Auth::user()->id;
            $totalLokasi = DataSpatial::where('user_id', $userOpdId)->count();
        } else {
            $totalLokasi = DataSpatial::count();
        }

        $totalOpd = Opd::count();
        
        // Build base query for aspirasi with OPD filter
        $aspirasiBaseQuery = DB::table('aspirasi');
        $aspirasiBaseQuery = $this->applyOpdFilterToRawQuery($aspirasiBaseQuery);
        
        $totalPendingAspirasi = (clone $aspirasiBaseQuery)->where('aspirasi.status', 'pending')->count();
        $totalAspirasi = (clone $aspirasiBaseQuery)->count();
        $totalSelesaiAspirasi = (clone $aspirasiBaseQuery)->where('aspirasi.status', 'selesai')->count();

        // Get monthly data for current year
        $currentYear = date('Y');
        $monthlyAspirasi = $this->getMonthlyAspirasiData($currentYear);
        
        // Get category distribution
        $categoryData = $this->getCategoryDistributionForDashboard();
        
        // Get recent aspirasi with OPD filter
        if ($this->isAdminOpd()) {
            $userOpdId = Auth::user()->opd_id;
            $recentAspirasi = DB::table('aspirasi')
                ->join('kategori_aspirasi', 'aspirasi.kategori_aspirasi_id', '=', 'kategori_aspirasi.id')
                ->where('kategori_aspirasi.opd_id', $userOpdId)
                ->orderBy('aspirasi.created_at', 'desc')
                ->limit(5)
                ->select('aspirasi.*', 'kategori_aspirasi.nama_kategori')
                ->get();
        } else {
            $recentAspirasi = DB::table('aspirasi')
                ->join('kategori_aspirasi', 'aspirasi.kategori_aspirasi_id', '=', 'kategori_aspirasi.id')
                ->orderBy('aspirasi.created_at', 'desc')
                ->limit(5)
                ->select('aspirasi.*', 'kategori_aspirasi.nama_kategori')
                ->get();
        }

        // Get visitor data - HANYA untuk non admin-opd
        $visitorData = [];
        $totalVisitors = 0;
        $todayVisitors = 0;
        $availableYears = [];
        
        if (!$this->isAdminOpd()) {
            $totalVisitors = Visitor::count();
            $todayVisitors = Visitor::whereDate('created_at', today())->count();
            $visitorData = $this->getMonthlyVisitorData($currentYear);
            
            // Get available years for visitor data - FIXED FOR POSTGRESQL
            $availableYears = Visitor::selectRaw('EXTRACT(YEAR FROM created_at) as year')
                ->distinct()
                ->orderBy('year', 'desc')
                ->pluck('year')
                ->toArray();
            
            if (empty($availableYears)) {
                $availableYears = [$currentYear];
            }
        }

        return view('dashboard', compact(
            'totalLokasi',
            'totalOpd',
            'totalPendingAspirasi',
            'totalAspirasi',
            'totalSelesaiAspirasi',
            'monthlyAspirasi',
            'categoryData',
            'recentAspirasi',
            'totalVisitors',
            'todayVisitors',
            'visitorData',
            'availableYears'
        ));
    }

    /**
     * Get monthly visitor data for chart - FIXED FOR POSTGRESQL
     */
    private function getMonthlyVisitorData($year)
    {
        $monthlyData = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $total = Visitor::whereRaw('EXTRACT(YEAR FROM created_at) = ?', [$year])
                ->whereRaw('EXTRACT(MONTH FROM created_at) = ?', [$month])
                ->count();
                
            $unique = Visitor::whereRaw('EXTRACT(YEAR FROM created_at) = ?', [$year])
                ->whereRaw('EXTRACT(MONTH FROM created_at) = ?', [$month])
                ->distinct('ip')
                ->count();

            $monthlyData['total'][] = $total;
            $monthlyData['unique'][] = $unique;
        }

        return $monthlyData;
    }

    /**
     * Get visitor statistics by year and month filter - FIXED FOR POSTGRESQL
     */
    public function getVisitorStatistics(Request $request)
    {
        // Cek jika user adalah admin-opd, tolak akses
        if ($this->isAdminOpd()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied for admin OPD'
            ], 403);
        }

        $year = $request->get('year', date('Y'));
        $month = $request->get('month');

        try {
            $query = Visitor::whereRaw('EXTRACT(YEAR FROM created_at) = ?', [$year]);
            
            if ($month && $month !== 'all') {
                $query->whereRaw('EXTRACT(MONTH FROM created_at) = ?', [$month]);
            }

            // Basic statistics
            $stats = [
                'total' => $query->count(),
                'unique' => $query->distinct('ip')->count(),
                'with_location' => $query->whereNotNull('latitude')->count(),
                'today' => Visitor::whereDate('created_at', today())->count()
            ];

            // Monthly breakdown
            $monthly = $this->getMonthlyVisitorData($year);

            // Daily breakdown for selected month (if month is specified)
            $daily = [];
            if ($month && $month !== 'all') {
                $daily = $this->getDailyVisitorData($year, $month);
            }

            // Top countries
            $countries = Visitor::whereRaw('EXTRACT(YEAR FROM created_at) = ?', [$year])
                ->when($month && $month !== 'all', function($q) use ($month) {
                    return $q->whereRaw('EXTRACT(MONTH FROM created_at) = ?', [$month]);
                })
                ->whereNotNull('country')
                ->where('country', '!=', 'Local')
                ->select('country', DB::raw('count(*) as total'))
                ->groupBy('country')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get();

            // Top pages
            $pages = Visitor::whereRaw('EXTRACT(YEAR FROM created_at) = ?', [$year])
                ->when($month && $month !== 'all', function($q) use ($month) {
                    return $q->whereRaw('EXTRACT(MONTH FROM created_at) = ?', [$month]);
                })
                ->select('page_visited', DB::raw('count(*) as total'))
                ->groupBy('page_visited')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'monthly' => $monthly,
                'daily' => $daily,
                'countries' => $countries,
                'pages' => $pages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading visitor statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get daily visitor data for specific month - FIXED FOR POSTGRESQL
     */
    private function getDailyVisitorData($year, $month)
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $dailyData = ['total' => [], 'unique' => []];
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $total = Visitor::whereRaw('EXTRACT(YEAR FROM created_at) = ?', [$year])
                ->whereRaw('EXTRACT(MONTH FROM created_at) = ?', [$month])
                ->whereRaw('EXTRACT(DAY FROM created_at) = ?', [$day])
                ->count();
                
            $unique = Visitor::whereRaw('EXTRACT(YEAR FROM created_at) = ?', [$year])
                ->whereRaw('EXTRACT(MONTH FROM created_at) = ?', [$month])
                ->whereRaw('EXTRACT(DAY FROM created_at) = ?', [$day])
                ->distinct('ip')
                ->count();

            $dailyData['total'][] = $total;
            $dailyData['unique'][] = $unique;
        }

        return $dailyData;
    }

    /**
     * Get visitor map data - FIXED FOR POSTGRESQL
     */
    public function getVisitorMapData(Request $request)
    {
        // Cek jika user adalah admin-opd, tolak akses
        if ($this->isAdminOpd()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied for admin OPD'
            ], 403);
        }

        $year = $request->get('year', date('Y'));
        $month = $request->get('month');

        $query = Visitor::select('latitude', 'longitude', 'city', 'country', 'created_at', 'page_visited')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw('EXTRACT(YEAR FROM created_at) = ?', [$year]);

        if ($month && $month !== 'all') {
            $query->whereRaw('EXTRACT(MONTH FROM created_at) = ?', [$month]);
        }

        $visitors = $query->latest()
            ->limit(500)
            ->get()
            ->map(function ($visitor) {
                return [
                    'lat' => (float) $visitor->latitude,
                    'lng' => (float) $visitor->longitude,
                    'city' => $visitor->city,
                    'country' => $visitor->country,
                    'page' => $visitor->page_visited,
                    'date' => $visitor->created_at->format('d/m/Y H:i')
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $visitors
        ]);
    }
    /**
     * Get available years from aspirasi data - FIXED FOR POSTGRESQL
     */
    public function getAvailableYears()
    {
        $query = DB::table('aspirasi')
            ->selectRaw('EXTRACT(YEAR FROM aspirasi.created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc');

        // Apply OPD filter
        $query = $this->applyOpdFilterToRawQuery($query);

        $years = $query->pluck('year')->toArray();

        return response()->json([
            'success' => true,
            'years' => $years
        ]);
    }

    /**
     * Get kategori aspirasi for dropdown (filtered by OPD if admin OPD)
     */
    public function getCategories()
    {
        $query = DB::table('kategori_aspirasi')
            ->select('id', 'nama_kategori as nama')
            ->orderBy('nama_kategori', 'asc');

        // Filter categories by OPD for admin OPD
        if ($this->isAdminOpd()) {
            $userOpdId = Auth::user()->opd_id;
            $query->where('opd_id', $userOpdId);
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Get comprehensive statistics for dashboard - FIXED FOR POSTGRESQL
     */
    public function getStatistics(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $categoryId = $request->get('category');

        try {
            // Build base query with OPD filter
            $baseQuery = DB::table('aspirasi')
                ->whereRaw('EXTRACT(YEAR FROM aspirasi.created_at) = ?', [$year]);

            // Apply OPD filter
            $baseQuery = $this->applyOpdFilterToRawQuery($baseQuery);

            if ($categoryId) {
                // Ensure the category belongs to user's OPD if admin OPD
                if ($this->isAdminOpd()) {
                    $userOpdId = Auth::user()->opd_id;
                    $categoryExists = DB::table('kategori_aspirasi')
                        ->where('id', $categoryId)
                        ->where('opd_id', $userOpdId)
                        ->exists();
                    
                    if (!$categoryExists) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Category not accessible'
                        ], 403);
                    }
                }
                $baseQuery->where('aspirasi.kategori_aspirasi_id', $categoryId);
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
     * Get basic statistics with month-over-month comparison - FIXED FOR POSTGRESQL
     */
    private function getBasicStats($baseQuery, $year, $categoryId)
    {
        $currentMonth = date('n');
        $currentYear = date('Y');

        // Current period stats
        $total = (clone $baseQuery)->count();
        $pending = (clone $baseQuery)->where('aspirasi.status', 'pending')->count();
        $diproses = (clone $baseQuery)->whereIn('aspirasi.status', ['diproses', 'ditindaklanjuti'])->count();
        $selesai = (clone $baseQuery)->where('aspirasi.status', 'selesai')->count();

        // Previous month comparison (if current year)
        $changes = ['totalChange' => 0, 'pendingChange' => 0, 'prosesChange' => 0, 'selesaiChange' => 0];
        
        if ($year == $currentYear && $currentMonth > 1) {
            $prevMonthQuery = DB::table('aspirasi')
                ->whereRaw('EXTRACT(YEAR FROM aspirasi.created_at) = ?', [$year])
                ->whereRaw('EXTRACT(MONTH FROM aspirasi.created_at) = ?', [$currentMonth - 1]);

            // Apply OPD filter to previous month query
            $prevMonthQuery = $this->applyOpdFilterToRawQuery($prevMonthQuery);

            if ($categoryId) {
                $prevMonthQuery->where('aspirasi.kategori_aspirasi_id', $categoryId);
            }

            $prevTotal = (clone $prevMonthQuery)->count();
            $prevPending = (clone $prevMonthQuery)->where('aspirasi.status', 'pending')->count();
            $prevDiproses = (clone $prevMonthQuery)->whereIn('aspirasi.status', ['diproses', 'ditindaklanjuti'])->count();
            $prevSelesai = (clone $prevMonthQuery)->where('aspirasi.status', 'selesai')->count();

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
     * Get monthly data for trend chart - FIXED FOR POSTGRESQL
     */
    private function getMonthlyData($baseQuery)
    {
        $monthlyData = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $total = (clone $baseQuery)
                ->whereRaw('EXTRACT(MONTH FROM aspirasi.created_at) = ?', [$month])
                ->count();
                
            $selesai = (clone $baseQuery)
                ->whereRaw('EXTRACT(MONTH FROM aspirasi.created_at) = ?', [$month])
                ->where('aspirasi.status', 'selesai')
                ->count();

            $monthlyData['total'][] = $total;
            $monthlyData['selesai'][] = $selesai;
        }

        return $monthlyData;
    }

    /**
     * Get monthly aspirasi data for dashboard main page - FIXED FOR POSTGRESQL
     */
    private function getMonthlyAspirasiData($year)
    {
        $monthlyData = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $query = DB::table('aspirasi')
                ->whereRaw('EXTRACT(YEAR FROM aspirasi.created_at) = ?', [$year])
                ->whereRaw('EXTRACT(MONTH FROM aspirasi.created_at) = ?', [$month]);

            // Apply OPD filter
            $query = $this->applyOpdFilterToRawQuery($query);

            $total = (clone $query)->count();
            $selesai = (clone $query)->where('aspirasi.status', 'selesai')->count();

            $monthlyData['total'][] = $total;
            $monthlyData['selesai'][] = $selesai;
        }

        return $monthlyData;
    }

    /**
     * Get category distribution for doughnut chart - FIXED FOR POSTGRESQL
     */
    private function getCategoryDistribution($year, $categoryId = null)
    {
        $query = DB::table('aspirasi')
            ->join('kategori_aspirasi', 'aspirasi.kategori_aspirasi_id', '=', 'kategori_aspirasi.id')
            ->select('kategori_aspirasi.nama_kategori as nama_kategori', DB::raw('COUNT(*) as total'))
            ->whereRaw('EXTRACT(YEAR FROM aspirasi.created_at) = ?', [$year])
            ->groupBy('kategori_aspirasi.id', 'kategori_aspirasi.nama_kategori')
            ->orderBy('total', 'desc');

        // Apply OPD filter
        if ($this->isAdminOpd()) {
            $userOpdId = Auth::user()->opd_id;
            $query->where('kategori_aspirasi.opd_id', $userOpdId);
        }

        if ($categoryId) {
            $query->where('aspirasi.kategori_aspirasi_id', $categoryId);
        }

        $results = $query->get();

        return [
            'labels' => $results->pluck('nama_kategori')->toArray(),
            'values' => $results->pluck('total')->toArray()
        ];
    }

    /**
     * Get category distribution for main dashboard - FIXED FOR POSTGRESQL
     */
    private function getCategoryDistributionForDashboard()
    {
        $currentYear = date('Y');
        
        $query = DB::table('aspirasi')
            ->join('kategori_aspirasi', 'aspirasi.kategori_aspirasi_id', '=', 'kategori_aspirasi.id')
            ->select('kategori_aspirasi.nama_kategori as nama_kategori', DB::raw('COUNT(*) as total'))
            ->whereRaw('EXTRACT(YEAR FROM aspirasi.created_at) = ?', [$currentYear])
            ->groupBy('kategori_aspirasi.id', 'kategori_aspirasi.nama_kategori')
            ->orderBy('total', 'desc')
            ->limit(6);

        // Apply OPD filter
        if ($this->isAdminOpd()) {
            $userOpdId = Auth::user()->opd_id;
            $query->where('kategori_aspirasi.opd_id', $userOpdId);
        }

        $results = $query->get();

        return [
            'labels' => $results->pluck('nama_kategori')->toArray(),
            'values' => $results->pluck('total')->toArray()
        ];
    }

    /**
     * Get status distribution by category - FIXED FOR POSTGRESQL
     */
    private function getStatusByCategory($year, $categoryId = null)
    {
        $query = DB::table('aspirasi')
            ->join('kategori_aspirasi', 'aspirasi.kategori_aspirasi_id', '=', 'kategori_aspirasi.id')
            ->select(
                'kategori_aspirasi.nama_kategori as category',
                DB::raw('SUM(CASE WHEN aspirasi.status = \'pending\' THEN 1 ELSE 0 END) as pending'),
                DB::raw('SUM(CASE WHEN aspirasi.status IN (\'diproses\', \'ditindaklanjuti\') THEN 1 ELSE 0 END) as diproses'),
                DB::raw('SUM(CASE WHEN aspirasi.status = \'selesai\' THEN 1 ELSE 0 END) as selesai')
            )
            ->whereRaw('EXTRACT(YEAR FROM aspirasi.created_at) = ?', [$year])
            ->groupBy('kategori_aspirasi.id', 'kategori_aspirasi.nama_kategori')
            ->orderBy('kategori_aspirasi.nama_kategori');

        // Apply OPD filter
        if ($this->isAdminOpd()) {
            $userOpdId = Auth::user()->opd_id;
            $query->where('kategori_aspirasi.opd_id', $userOpdId);
        }

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
            ->select('aspirasi.jenis_aspirasi', DB::raw('COUNT(*) as total'))
            ->groupBy('aspirasi.jenis_aspirasi')
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
     * Get top performing categories - FIXED FOR POSTGRESQL
     */
    public function getTopCategories(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $limit = $request->get('limit', 5);

        $query = DB::table('aspirasi')
            ->join('kategori_aspirasi', 'aspirasi.kategori_aspirasi_id', '=', 'kategori_aspirasi.id')
            ->select(
                'kategori_aspirasi.nama_kategori as nama_kategori',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN aspirasi.status = \'selesai\' THEN 1 ELSE 0 END) as selesai'),
                DB::raw('ROUND((SUM(CASE WHEN aspirasi.status = \'selesai\' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as completion_rate')
            )
            ->whereRaw('EXTRACT(YEAR FROM aspirasi.created_at) = ?', [$year])
            ->groupBy('kategori_aspirasi.id', 'kategori_aspirasi.nama_kategori')
            ->orderBy('total', 'desc')
            ->limit($limit);

        // Apply OPD filter
        if ($this->isAdminOpd()) {
            $userOpdId = Auth::user()->opd_id;
            $query->where('kategori_aspirasi.opd_id', $userOpdId);
        }

        $topCategories = $query->get();

        return response()->json([
            'success' => true,
            'data' => $topCategories
        ]);
    }

    /**
     * Get response time analytics - FIXED FOR POSTGRESQL
     */
    public function getResponseTimeAnalytics(Request $request)
    {
        $year = $request->get('year', date('Y'));

        $query = DB::table('aspirasi')
            ->select(
                DB::raw('AVG(aspirasi.tanggal_respon::date - aspirasi.created_at::date) as avg_response_days'),
                DB::raw('MIN(aspirasi.tanggal_respon::date - aspirasi.created_at::date) as min_response_days'),
                DB::raw('MAX(aspirasi.tanggal_respon::date - aspirasi.created_at::date) as max_response_days'),
                DB::raw('COUNT(CASE WHEN aspirasi.tanggal_respon IS NOT NULL THEN 1 END) as responded_count'),
                DB::raw('COUNT(*) as total_count')
            )
            ->whereRaw('EXTRACT(YEAR FROM aspirasi.created_at) = ?', [$year]);

        // Apply OPD filter
        $query = $this->applyOpdFilterToRawQuery($query);

        $responseTime = $query->first();

        return response()->json([
            'success' => true,
            'data' => $responseTime
        ]);
    }

    /**
     * Get dashboard statistics for widgets - FIXED FOR POSTGRESQL
     */
    public function getDashboardStats()
    {
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        // Base query with OPD filter
        $baseQuery = DB::table('aspirasi')->whereRaw('EXTRACT(YEAR FROM aspirasi.created_at) = ?', [$currentYear]);
        $baseQuery = $this->applyOpdFilterToRawQuery($baseQuery);
        
        // Total counts
        $totalAspirasi = (clone $baseQuery)->count();
        $totalPending = (clone $baseQuery)->where('aspirasi.status', 'pending')->count();
        $totalSelesai = (clone $baseQuery)->where('aspirasi.status', 'selesai')->count();
        $totalOpd = Opd::count();
        $totalLokasi = DataSpatial::count();
        
        // This month vs last month
        $thisMonthQuery = (clone $baseQuery)->whereRaw('EXTRACT(MONTH FROM aspirasi.created_at) = ?', [$currentMonth]);
        $thisMonth = $thisMonthQuery->count();
                            
        $lastMonthQuery = (clone $baseQuery);
        $lastMonth = $currentMonth > 1 ? 
            $lastMonthQuery->whereRaw('EXTRACT(MONTH FROM aspirasi.created_at) = ?', [$currentMonth - 1])->count() : 0;
        
        $monthlyChange = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;
        
        // Response rate
        $totalWithResponseQuery = clone $baseQuery;
        $totalWithResponse = $totalWithResponseQuery->whereNotNull('aspirasi.tanggal_respon')->count();
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
        
        // Get available years for filter with OPD restriction - FIXED FOR POSTGRESQL
        $availableYearsQuery = DB::table('aspirasi')
            ->selectRaw('EXTRACT(YEAR FROM aspirasi.created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc');

        $availableYearsQuery = $this->applyOpdFilterToRawQuery($availableYearsQuery);
        $availableYears = $availableYearsQuery->pluck('year');

        // Get categories for filter (filtered by OPD if admin OPD)
        $categoriesQuery = DB::table('kategori_aspirasi')
            ->select('id', 'nama_kategori as nama')
            ->orderBy('nama_kategori');

        if ($this->isAdminOpd()) {
            $userOpdId = Auth::user()->opd_id;
            $categoriesQuery->where('opd_id', $userOpdId);
        }

        $categories = $categoriesQuery->get();

        return view('backend.pages.aspirasi.statistics', compact('currentYear', 'availableYears', 'categories'));
    }
}