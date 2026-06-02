<?php

namespace App\Services;

use App\Models\Aspirasi;
use App\Models\Visitor;
use Carbon\Carbon;

class WebStatisticService
{
    public function getData(): array
    {
        return [
            'visitors'   => $this->visitorStats(),
            'aspirations' => $this->aspirasiStats(),
        ];
    }

    private function visitorStats(): array
    {
        return [
            'today' => Visitor::today()->count(),
            'week'  => Visitor::thisWeek()->count(),
            'month' => Visitor::thisMonth()->count(),
            'year'  => Visitor::whereYear('created_at', Carbon::now()->year)->count(),
            'total' => Visitor::count(),
        ];
    }

    private function aspirasiStats(): array
    {
        return [
            'kritik_dan_saran' => Aspirasi::where('jenis_aspirasi', 'kritik_dan_saran')->count(),
            'usulan'           => Aspirasi::where('jenis_aspirasi', 'usulan')->count(),
        ];
    }
}
