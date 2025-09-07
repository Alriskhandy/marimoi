<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Visitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip',
        'user_agent',
        'latitude',
        'longitude',
        'country',
        'city',
        'page_visited'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Scopes for filtering
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    // Accessors
    public function getBrowserAttribute()
    {
        if (!$this->user_agent) return 'Unknown';
        
        $browsers = [
            'Chrome' => '/Chrome/i',
            'Firefox' => '/Firefox/i',
            'Safari' => '/Safari/i',
            'Edge' => '/Edge/i',
            'Opera' => '/Opera/i',
            'Internet Explorer' => '/MSIE/i'
        ];

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $this->user_agent)) {
                return $browser;
            }
        }

        return 'Other';
    }

    public function getDeviceTypeAttribute()
    {
        if (!$this->user_agent) return 'Unknown';
        
        if (preg_match('/Mobile|Android|iPhone|iPad/i', $this->user_agent)) {
            if (preg_match('/Tablet|iPad/i', $this->user_agent)) {
                return 'Tablet';
            }
            return 'Mobile';
        }
        
        return 'Desktop';
    }

    public function getOperatingSystemAttribute()
    {
        if (!$this->user_agent) return 'Unknown';
        
        $os_array = [
            'Windows' => '/Windows/i',
            'Mac OS' => '/Mac/i',
            'Linux' => '/Linux/i',
            'Android' => '/Android/i',
            'iOS' => '/iPhone|iPad/i'
        ];

        foreach ($os_array as $os => $pattern) {
            if (preg_match($pattern, $this->user_agent)) {
                return $os;
            }
        }

        return 'Unknown';
    }

    // Static methods for analytics
    public static function getTodayStats()
    {
        return [
            'total_visits' => self::today()->count(),
            'unique_visitors' => self::today()->distinct('ip')->count(),
            'page_views' => self::today()->count(),
            'bounce_rate' => self::calculateBounceRate('today')
        ];
    }

    public static function getWeeklyStats()
    {
        return [
            'total_visits' => self::thisWeek()->count(),
            'unique_visitors' => self::thisWeek()->distinct('ip')->count(),
            'page_views' => self::thisWeek()->count(),
            'bounce_rate' => self::calculateBounceRate('week')
        ];
    }

    public static function getMonthlyStats()
    {
        return [
            'total_visits' => self::thisMonth()->count(),
            'unique_visitors' => self::thisMonth()->distinct('ip')->count(),
            'page_views' => self::thisMonth()->count(),
            'bounce_rate' => self::calculateBounceRate('month')
        ];
    }

    public static function getTopPages($period = 'today', $limit = 10)
    {
        $query = self::query();
        
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

        return $query->select('page_visited')
                    ->selectRaw('count(*) as visits')
                    ->groupBy('page_visited')
                    ->orderByDesc('visits')
                    ->limit($limit)
                    ->get();
    }

    public static function getTopCountries($period = 'today', $limit = 10)
    {
        $query = self::query();
        
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

        return $query->select('country')
                    ->selectRaw('count(*) as visits')
                    ->whereNotNull('country')
                    ->groupBy('country')
                    ->orderByDesc('visits')
                    ->limit($limit)
                    ->get();
    }

    public static function getBrowserStats($period = 'today')
    {
        $query = self::query();
        
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
        $browsers = $visitors->groupBy('browser')->map->count();
        
        return $browsers->sortDesc();
    }

    public static function calculateBounceRate($period)
    {
        $query = self::query();
        
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

    // Additional helper methods
    public static function getHourlyStats($period = 'today')
    {
        $query = self::select(
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

    public static function getDeviceStats($period = 'today')
    {
        $query = self::query();
        
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

    public static function getDailyVisits($days = 7)
    {
        return self::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as visits'),
                    DB::raw('COUNT(DISTINCT ip) as unique_visitors')
                )
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
    }

    // Real-time stats
    public static function getLiveStats()
    {
        return [
            'online_now' => self::where('created_at', '>=', Carbon::now()->subMinutes(5))->distinct('ip')->count(),
            'last_hour' => self::where('created_at', '>=', Carbon::now()->subHour())->count(),
            'today_unique' => self::today()->distinct('ip')->count(),
            'total_pages_today' => self::today()->distinct('page_visited')->count()
        ];
    }

    // Geographic methods
    public static function getCountryStats($limit = 20)
    {
        return self::select('country')
                   ->selectRaw('count(*) as visits')
                   ->selectRaw('count(distinct ip) as unique_visitors')
                   ->whereNotNull('country')
                   ->groupBy('country')
                   ->orderByDesc('visits')
                   ->limit($limit)
                   ->get();
    }

    public static function getCityStats($limit = 20)
    {
        return self::select('city', 'country')
                   ->selectRaw('count(*) as visits')
                   ->selectRaw('count(distinct ip) as unique_visitors')
                   ->whereNotNull('city')
                   ->groupBy('city', 'country')
                   ->orderByDesc('visits')
                   ->limit($limit)
                   ->get();
    }

    // Page analysis
    public static function getPageAnalytics($period = 'today')
    {
        $query = self::query();
        
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

        return $query->select('page_visited')
                    ->selectRaw('count(*) as total_visits')
                    ->selectRaw('count(distinct ip) as unique_visitors')
                    ->selectRaw('avg(case when (select count(*) from visitors v2 where v2.ip = visitors.ip and DATE(v2.created_at) = DATE(visitors.created_at)) = 1 then 1 else 0 end) * 100 as bounce_rate')
                    ->groupBy('page_visited')
                    ->orderByDesc('total_visits')
                    ->get();
    }

    // Session tracking
    public function getSessionDuration()
    {
        $sessionVisits = self::where('ip', $this->ip)
                            ->whereDate('created_at', $this->created_at->toDateString())
                            ->orderBy('created_at')
                            ->get();

        if ($sessionVisits->count() < 2) {
            return 0;
        }

        $first = $sessionVisits->first()->created_at;
        $last = $sessionVisits->last()->created_at;

        return $first->diffInMinutes($last);
    }

    public function getSessionPageViews()
    {
        return self::where('ip', $this->ip)
                  ->whereDate('created_at', $this->created_at->toDateString())
                  ->count();
    }

    // Traffic source detection (basic)
    public function getTrafficSource()
    {
        if (!$this->user_agent) return 'Direct';

        // Check for social media
        if (preg_match('/facebook|twitter|instagram|linkedin|pinterest/i', $this->user_agent)) {
            return 'Social Media';
        }

        // Check for search engines
        if (preg_match('/google|bing|yahoo|duckduckgo/i', $this->user_agent)) {
            return 'Search Engine';
        }

        return 'Direct';
    }
}