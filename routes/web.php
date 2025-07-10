<?php

use App\Http\Controllers\FrontendController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontendController::class, 'index'])->name('beranda');
Route::get('/peta-gis', [FrontendController::class, 'showMap'])->name('tampil.peta');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});



Route::get('/lokasi', [LokasiController::class, 'index'])->name('lokasi.index');
Route::get('/lokasi/create', [LokasiController::class, 'create'])->name('lokasi.create');
Route::post('/lokasi', [LokasiController::class, 'store'])->name('lokasi.store');
Route::get('/lokasi/{id}/edit', [LokasiController::class, 'edit'])->name('lokasi.edit');
Route::put('/lokasi/{id}', [LokasiController::class, 'update'])->name('lokasi.update');
Route::delete('/lokasi/{id}', [LokasiController::class, 'destroy'])->name('lokasi.destroy');

Route::get('/peta', [LokasiController::class, 'peta'])->name('lokasi.peta');
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
