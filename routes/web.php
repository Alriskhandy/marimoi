<?php

use App\Http\Controllers\FrontendController;
use App\Http\Controllers\LokasiController;
use Illuminate\Support\Facades\Route;

// Rute FIX
Route::get('/', [FrontendController::class, 'index'])->name('beranda');
Route::get('/proyek-strategis-daerah', [FrontendController::class, 'psd'])->name('tampil.psd');
Route::get('/proyek-strategis-nasional', [FrontendController::class, 'psn'])->name('tampil.psn');
Route::get('/prioritas-daerah', [FrontendController::class, 'prioritas'])->name('tampil.prioritas');
Route::get('/pokir-dprd', [FrontendController::class, 'pokir'])->name('tampil.pokir');

// Rute Tahap Pengembangan
Route::get('/usulan-musrenbang', [FrontendController::class, 'showMap'])->name('tampil.musrenbang');
Route::get('/aspirasi-masyarakat', [FrontendController::class, 'showMap'])->name('tampil.aspirasi');
Route::get('/peta-gis', [FrontendController::class, 'showMap'])->name('tampil.peta');

Route::get('/detail-psd/{id}', [FrontendController::class, 'detailPsd'])->name('detail.psd');
Route::get('/detail-psn/{id}', [FrontendController::class, 'detailPsn'])->name('detail.psn');
Route::get('/detail-pokir/{id}', [FrontendController::class, 'detailPokir'])->name('detail.pokir');

// Rute API
Route::get('/psd-geojson', [FrontendController::class, 'psdGeojson']);
Route::get('/psn-geojson', [FrontendController::class, 'psnGeojson']);
Route::get('/rpjmd-geojson', [FrontendController::class, 'rpjmdGeojson']);
Route::get('/pokir-geojson', [FrontendController::class, 'pokirGeojson']);
Route::get('/geojson', [LokasiController::class, 'geojson'])->name('lokasi.geojson');

// Nanti di hapus
Route::prefix('api')->group(function (){
    
    // API DEFAULT - LOKASI
    Route::get('/categories', [LokasiController::class, 'getCategories'])->name('lokasi.categories');
    Route::get('/statistics', [LokasiController::class, 'getStatistics'])->name('lokasi.statistics');
    Route::get('/category/{kategori}', [LokasiController::class, 'getByCategory'])->name('lokasi.by-category');

    Route::get('/dbf/columns', [LokasiController::class, 'getDbfColumns'])->name('lokasi.dbf-columns');
    Route::get('/dbf/column/{column}/values', [LokasiController::class, 'getDbfColumnValues'])->name('lokasi.dbf-column-values');
});

require __DIR__.'/auth.php';
require __DIR__.'/backend.php';
