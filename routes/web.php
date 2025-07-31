<?php

use App\Http\Controllers\FrontendController;
use App\Http\Controllers\LokasiController;
use Illuminate\Support\Facades\Route;

// HALAMAN //
Route::get('/', [FrontendController::class, 'index'])->name('beranda');
Route::get('/proyek-strategis-daerah', [FrontendController::class, 'psd'])->name('tampil.psd');
Route::get('/proyek-strategis-nasional', [FrontendController::class, 'psn'])->name('tampil.psn');
Route::get('/prioritas-daerah', [FrontendController::class, 'prioritas'])->name('tampil.prioritas');
Route::get('/rpjmd', [FrontendController::class, 'rpjmd'])->name('tampil.rpjmd');
Route::get('/usulan-musrenbang', [FrontendController::class, 'musrenbang'])->name('tampil.musrenbang');
Route::get('/pokir-dprd', [FrontendController::class, 'pokir'])->name('tampil.pokir');
Route::get('/aspirasi-masyarakat', [FrontendController::class, 'aspirasi'])->name('tampil.aspirasi');

// HALAMAN DETAIL //
Route::get('/proyek-strategis-daerah/{id}', [FrontendController::class, 'detailPsd'])->name('detail.psd');
Route::get('/proyek-strategis-nasional/{id}', [FrontendController::class, 'detailPsn'])->name('detail.psn');
Route::get('/rpjmd/{id}', [FrontendController::class, 'detailRpjmd'])->name('detail.rpjmd');
Route::get('/pokir-dprd/{id}', [FrontendController::class, 'detailPokir'])->name('detail.pokir');
Route::get('/usulan-musrenbang/{id}', [FrontendController::class, 'detailMusrenbang'])->name('detail.musrenbang');

// FEEDBACK //
Route::post('/proyek-strategis-daerah/{id}', [FrontendController::class, 'store'])->name('feedback.store');

// API GEOJSON //
Route::prefix('geojson')->group(function(){
    Route::get('/proyek-strategis-daerah', [FrontendController::class, 'psdGeojson']);
    Route::get('/proyek-strategis-nasional', [FrontendController::class, 'psnGeojson']);
    Route::get('/rpjmd', [FrontendController::class, 'rpjmdGeojson']);
    Route::get('/pokir-dprd', [FrontendController::class, 'pokirGeojson']);
    Route::get('/usulan-musrenbang', [FrontendController::class, 'musrenbangGeojson']);
});

Route::get('/geojson', [LokasiController::class, 'geojson'])->name('lokasi.geojson');

// TAHAP PENGEMBANGAN //
Route::get('/peta-gis', [FrontendController::class, 'showMap'])->name('tampil.peta');

require __DIR__.'/auth.php';
require __DIR__.'/backend.php';
