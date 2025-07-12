<?php

use App\Http\Controllers\FrontendController;
use App\Http\Controllers\KategoriLayerController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\MalukuUtaraController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectFeedbackController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontendController::class, 'index'])->name('beranda');
Route::get('/peta-gis', [FrontendController::class, 'showMap'])->name('tampil.peta');

Route::get('/geojson', [LokasiController::class, 'geojson'])->name('lokasi.geojson');

// Routes untuk fitur layer control
Route::get('/api/categories', [LokasiController::class, 'getCategories'])->name('lokasi.categories');
Route::get('/api/statistics', [LokasiController::class, 'getStatistics'])->name('lokasi.statistics');
Route::get('/api/category/{kategori}', [LokasiController::class, 'getByCategory'])->name('lokasi.by-category');

// Routes baru untuk atribut DBF
Route::get('/api/dbf/columns', [LokasiController::class, 'getDbfColumns'])->name('lokasi.dbf-columns');
Route::get('/api/dbf/column/{column}/values', [LokasiController::class, 'getDbfColumnValues'])->name('lokasi.dbf-column-values');

Route::post('/debug-shapefile', [LokasiController::class, 'debugShapefile'])->name('lokasi.debug');


require __DIR__.'/auth.php';
require __DIR__.'/backend.php';
