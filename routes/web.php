<?php

use App\Http\Controllers\FrontendController;
use App\Http\Controllers\LokasiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProyekStrategisDaerahController;

Route::get('/', [FrontendController::class, 'index'])->name('beranda');
Route::get('/peta-psd', [FrontendController::class, 'psd'])->name('tampil.psd');
Route::get('/peta-psn', [FrontendController::class, 'psn'])->name('tampil.psn');
Route::get('/peta-rpjmd', [FrontendController::class, 'rpjmd'])->name('tampil.rpjmd');
Route::get('/peta-pokir', [FrontendController::class, 'pokir'])->name('tampil.pokir');
Route::get('/peta-gis', [FrontendController::class, 'showMap'])->name('tampil.peta');
Route::get('/detail/{id}', [FrontendController::class, 'showDetail'])->name('tampil.detail');


Route::get('/psd-geojson', [FrontendController::class, 'psdGeojson']);
Route::get('/psn-geojson', [FrontendController::class, 'psnGeojson']);
Route::get('/rpjmd-geojson', [FrontendController::class, 'rpjmdGeojson']);
Route::get('/pokir-geojson', [FrontendController::class, 'pokirGeojson']);
Route::get('/geojson', [LokasiController::class, 'geojson'])->name('lokasi.geojson');

Route::prefix('api')->group(function (){
    
    // API DEFAULT - LOKASI
    Route::get('/categories', [LokasiController::class, 'getCategories'])->name('lokasi.categories');
    Route::get('/statistics', [LokasiController::class, 'getStatistics'])->name('lokasi.statistics');
    Route::get('/category/{kategori}', [LokasiController::class, 'getByCategory'])->name('lokasi.by-category');

    Route::get('/dbf/columns', [LokasiController::class, 'getDbfColumns'])->name('lokasi.dbf-columns');
    Route::get('/dbf/column/{column}/values', [LokasiController::class, 'getDbfColumnValues'])->name('lokasi.dbf-column-values');

    // PROYEK STRATEGIS DAERAH
    Route::prefix('/psd')->group(function () {
        Route::get('/geojson', [FrontendController::class, 'psdGeojson'])->name('psd.geojson');
        Route::get('/layer/{year}', [FrontendController::class, 'psdGeojsonYear'])->name('api.psd.layer');
        Route::get('/years', [ProyekStrategisDaerahController::class, 'getAvailableYearsApi'])->name('api.psd.years');
        Route::get('/statistics/{year}', [ProyekStrategisDaerahController::class, 'getStatisticsByYear'])->name('api.psd.statistics');
    });

});

require __DIR__.'/auth.php';
require __DIR__.'/backend.php';
