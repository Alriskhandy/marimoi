<?php

namespace App\Http\Controllers;

use App\Models\DataSpatial;
use App\Models\Opd;
use App\Models\Aspirasi;
use App\Models\KategoriAspirasi;


class DashboardController extends Controller
{

public function index()
{
    $totalLokasi = DataSpatial::count();
    $totalOpd = Opd::count();
    $totalPendingAspirasi = Aspirasi::where('status', 'pending')->count();

    return view('dashboard', compact(
        'totalLokasi',
        'totalOpd',
        'totalPendingAspirasi',
    ));
}

    //
}
