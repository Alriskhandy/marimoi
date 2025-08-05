<?php

use App\Http\Controllers\AspirasiController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DataSpatialController;
use App\Http\Controllers\KategoriAspirasiController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\ProjectFeedbackController;
use App\Http\Controllers\MalukuUtaraController;
use App\Http\Controllers\RoleController;
use App\Models\DataSpatial;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Unified Structure
|--------------------------------------------------------------------------
|
| This file contains the refactored routes using the unified DataSpatial
| controller and Category system. All old separate controllers are replaced
| with the unified approach.
|
*/

// Dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Profile Management
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| dashboard Panel Routes - Unified Data Spatial Management
|--------------------------------------------------------------------------
*/

Route::prefix('dashboard')->middleware(['auth'])->group(function () {
    
    // === UNIFIED DATA SPATIAL ROUTES ===
    Route::prefix('data-spatial')->name('data-spatial.')->group(function () {
        Route::get('/', [DataSpatialController::class, 'index'])->name('index');
        Route::get('/create', [DataSpatialController::class, 'create'])->name('create');
        Route::post('/store', [DataSpatialController::class, 'store'])->name('store');
        Route::get('/{uuid}/edit', [DataSpatialController::class, 'edit'])->name('edit');
        Route::put('/{uuid}', [DataSpatialController::class, 'update'])->name('update');
        Route::delete('/{uuid}', [DataSpatialController::class, 'destroy'])->name('destroy');
        
        // Debug routes for file uploads
        Route::post('/debug/shapefile', [DataSpatialController::class, 'debugShapefile'])->name('debug.shapefile');
        Route::post('/debug/kmz', [DataSpatialController::class, 'debugKmz'])->name('debug.kmz');
        
        // Detail endpoint for modal
       Route::get('/{uuid}/details', function($uuid) {
            $data = \App\Models\DataSpatial::with('kategori')->where('uuid', $uuid)->first();
            return response()->json([
                'success' => $data ? true : false,
                'data' => $data,
                'message' => $data ? 'Data found' : 'Data not found'
            ]);
        })->name('data-spatial.details');

    });

    // === PETA RPJMD (tematik) ROUTES ===
    Route::prefix('tematik')->name('tematik.')->group(function () {
        Route::get('/', [DataSpatialController::class, 'indextematik'])->name('index');
        Route::get('/create', function() {
            return redirect()->route('data-spatial.create') . '?type=tematik';
        })->name('create');
        Route::get('/{uuid}/edit', function($id) {
            return redirect()->route('data-spatial.edit', $id);
        })->name('edit');
        Route::put('/{uuid}', function($id) {
            return redirect()->route('data-spatial.update', $id);
        })->name('update');
        Route::delete('/{uuid}', function($id) {
            return redirect()->route('data-spatial.destroy', $id);
        })->name('destroy');
        
        // API routes
        Route::get('/geojson', [DataSpatialController::class, 'geojson'])
            ->defaults('data_type', 'tematik')->name('geojson');
        Route::get('/statistics', [DataSpatialController::class, 'getStatistics'])
            ->defaults('data_type', 'tematik')->name('statistics');
        Route::get('/categories', [DataSpatialController::class, 'getCategories'])
            ->defaults('data_type', 'tematik')->name('categories');
        Route::get('/dbf-columns', [DataSpatialController::class, 'getDbfColumns'])
            ->defaults('data_type', 'tematik')->name('dbf.columns');
        Route::get('/dbf-columns/{column}/values', [DataSpatialController::class, 'getDbfColumnValues'])
            ->defaults('data_type', 'tematik')->name('dbf.values');
        
        // Peta view
        Route::get('/peta', function() {
            return view('backend.pages.maps.tematik');
        })->name('peta');
    });

    // === USULAN MUSRENBANG ROUTES ===
    Route::prefix('usulan-musrenbang')->name('usulan-musrenbang.')->group(function () {
        Route::get('/', [DataSpatialController::class, 'indexUsulanmusrenbang'])->name('index');
        Route::get('/create', function() {
            return redirect()->route('data-spatial.create') . '?type=usulan_musrenbang';
        })->name('create');
        Route::get('/{uuid}/edit', function($id) {
            return redirect()->route('data-spatial.edit', $id);
        })->name('edit');
        Route::put('/{uuid}', function($id) {
            return redirect()->route('data-spatial.update', $id);
        })->name('update');
        Route::delete('/{uuid}', function($id) {
            return redirect()->route('data-spatial.destroy', $id);
        })->name('destroy');
        
        // API routes
        Route::get('/geojson', [DataSpatialController::class, 'geojson'])
            ->defaults('data_type', 'usulan_musrenbang')->name('geojson');
        Route::get('/statistics', [DataSpatialController::class, 'getStatistics'])
            ->defaults('data_type', 'usulan_musrenbang')->name('statistics');
        Route::get('/categories', [DataSpatialController::class, 'getCategories'])
            ->defaults('data_type', 'usulan_musrenbang')->name('categories');
        Route::get('/dbf-columns', [DataSpatialController::class, 'getDbfColumns'])
            ->defaults('data_type', 'usulan_musrenbang')->name('dbf.columns');
        Route::get('/dbf-columns/{column}/values', [DataSpatialController::class, 'getDbfColumnValues'])
            ->defaults('data_type', 'usulan_musrenbang')->name('dbf.values');
        
        // Peta view
        Route::get('/peta', function() {
            return view('backend.pages.maps.usulan_musrenbang');
        })->name('peta');
    });

    // === POKIR DPRD ROUTES ===
    Route::prefix('pokir-dprd')->name('pokir-dprd.')->group(function () {
        Route::get('/', [DataSpatialController::class, 'indexPokirDprd'])->name('index');
        Route::get('/create', function() {
            return redirect()->route('data-spatial.create') . '?type=pokir_dprd';
        })->name('create');
        Route::get('/{uuid}/edit', function($id) {
            return redirect()->route('data-spatial.edit', $id);
        })->name('edit');
        Route::put('/{uuid}', function($id) {
            return redirect()->route('data-spatial.update', $id);
        })->name('update');
        Route::delete('/{uuid}', function($id) {
            return redirect()->route('data-spatial.destroy', $id);
        })->name('destroy');
        
        // API routes
        Route::get('/geojson', [DataSpatialController::class, 'geojson'])
            ->defaults('data_type', 'pokir_dprd')->name('geojson');
        Route::get('/statistics', [DataSpatialController::class, 'getStatistics'])
            ->defaults('data_type', 'pokir_dprd')->name('statistics');
        Route::get('/categories', [DataSpatialController::class, 'getCategories'])
            ->defaults('data_type', 'pokir_dprd')->name('categories');
        Route::get('/dbf-columns', [DataSpatialController::class, 'getDbfColumns'])
            ->defaults('data_type', 'pokir_dprd')->name('dbf.columns');
        Route::get('/dbf-columns/{column}/values', [DataSpatialController::class, 'getDbfColumnValues'])
            ->defaults('data_type', 'pokir_dprd')->name('dbf.values');
        
        // Peta view
        Route::get('/peta', function() {
            return view('backend.pages.maps.pokir_dprd');
        })->name('peta');
    });

    // === PROYEK STRATEGIS DAERAH ROUTES ===
    Route::prefix('proyek-strategis-daerah')->name('psd.')->group(function () {
        Route::get('/', [DataSpatialController::class, 'indexProyekStrategisDaerah'])->name('index');
        Route::get('/create', function() {
            return redirect()->route('data-spatial.create') . '?type=proyek_strategis&sub_type=daerah';
        })->name('create');
        
        // Routes berdasarkan tahun
        Route::get('/tahun/{year}', [DataSpatialController::class, 'indexProyekStrategisDaerah'])->name('tahun.show');
        Route::get('/tahun/{year}/create', function($year) {
            return redirect()->route('data-spatial.create') . "?type=proyek_strategis&sub_type=daerah&year={$year}";
        })->name('tahun.create');
        
        // API routes dengan tahun
        Route::get('/tahun/{year}/geojson', [DataSpatialController::class, 'geojson'])
             ->defaults('data_type', 'proyek_strategis')
             ->defaults('sub_type', 'daerah')
             ->name('tahun.geojson');
        Route::get('/tahun/{year}/statistics', [DataSpatialController::class, 'getStatistics'])
             ->defaults('data_type', 'proyek_strategis')
             ->defaults('sub_type', 'daerah')
             ->name('tahun.statistics');
        
        // API routes umum
        Route::get('/geojson', [DataSpatialController::class, 'geojson'])
             ->defaults('data_type', 'proyek_strategis')
             ->defaults('sub_type', 'daerah')
             ->name('geojson');
        Route::get('/statistics', [DataSpatialController::class, 'getStatistics'])
             ->defaults('data_type', 'proyek_strategis')
             ->defaults('sub_type', 'daerah')
             ->name('statistics');
        Route::get('/categories', [DataSpatialController::class, 'getCategories'])
             ->defaults('data_type', 'proyek_strategis')
             ->defaults('sub_type', 'daerah')
             ->name('categories');
        Route::get('/dbf-columns', [DataSpatialController::class, 'getDbfColumns'])
             ->defaults('data_type', 'proyek_strategis')
             ->defaults('sub_type', 'daerah')
             ->name('dbf.columns');
        Route::get('/dbf-columns/{column}/values', [DataSpatialController::class, 'getDbfColumnValues'])
             ->defaults('data_type', 'proyek_strategis')
             ->defaults('sub_type', 'daerah')
             ->name('dbf.values');
        
        // Peta view
        Route::get('/peta', function() {
            return view('backend.pages.maps.proyek_strategis_daerah');
        })->name('peta');
        Route::get('/peta/{year}', function($year) {
            return view('backend.pages.maps.proyek_strategis_daerah', compact('year'));
        })->name('peta.year');
    });

    // === PROYEK STRATEGIS NASIONAL ROUTES ===
    Route::prefix('proyek-strategis-nasional')->name('psn.')->group(function () {
        Route::get('/', [DataSpatialController::class, 'indexProyekStrategisNasional'])->name('index');
        Route::get('/create', function() {
            return redirect()->route('data-spatial.create') . '?type=proyek_strategis&sub_type=nasional';
        })->name('create');
        
        // Routes berdasarkan tahun
        Route::get('/tahun/{year}', [DataSpatialController::class, 'indexProyekStrategisNasional'])->name('tahun.show');
        Route::get('/tahun/{year}/create', function($year) {
            return redirect()->route('data-spatial.create') . "?type=proyek_strategis&sub_type=nasional&year={$year}";
        })->name('tahun.create');
        
        // API routes dengan tahun
        Route::get('/tahun/{year}/geojson', [DataSpatialController::class, 'geojson'])
             ->defaults('data_type', 'proyek_strategis')
             ->defaults('sub_type', 'nasional')
             ->name('tahun.geojson');
        Route::get('/tahun/{year}/statistics', [DataSpatialController::class, 'getStatistics'])
             ->defaults('data_type', 'proyek_strategis')
             ->defaults('sub_type', 'nasional')
             ->name('tahun.statistics');
        
        // API routes umum
        Route::get('/geojson', [DataSpatialController::class, 'geojson'])
             ->defaults('data_type', 'proyek_strategis')
             ->defaults('sub_type', 'nasional')
             ->name('geojson');
        Route::get('/statistics', [DataSpatialController::class, 'getStatistics'])
             ->defaults('data_type', 'proyek_strategis')
             ->defaults('sub_type', 'nasional')
             ->name('statistics');
        Route::get('/categories', [DataSpatialController::class, 'getCategories'])
             ->defaults('data_type', 'proyek_strategis')
             ->defaults('sub_type', 'nasional')
             ->name('categories');
        Route::get('/dbf-columns', [DataSpatialController::class, 'getDbfColumns'])
             ->defaults('data_type', 'proyek_strategis')
             ->defaults('sub_type', 'nasional')
             ->name('dbf.columns');
        Route::get('/dbf-columns/{column}/values', [DataSpatialController::class, 'getDbfColumnValues'])
             ->defaults('data_type', 'proyek_strategis')
             ->defaults('sub_type', 'nasional')
             ->name('dbf.values');
        
        // Peta view
        Route::get('/peta', function() {
            return view('backend.pages.maps.proyek_strategis_nasional');
        })->name('peta');
        Route::get('/peta/{year}', function($year) {
            return view('backend.pages.maps.proyek_strategis_nasional', compact('year'));
        })->name('peta.year');
    });

    // === UNIFIED CATEGORY MANAGEMENT ===
 Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/create', [CategoryController::class, 'create'])->name('create');
    Route::post('/store', [CategoryController::class, 'store'])->name('store');
    Route::get('/{id}', [CategoryController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [CategoryController::class, 'edit'])->name('edit');
    Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
    Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
    
    // API routes untuk categories
    Route::get('/api/by-type/{type}', [CategoryController::class, 'getByType'])->name('api.by-type');
    Route::get('/api/tree/{type?}', [CategoryController::class, 'getTree'])->name('api.tree');
    Route::get('/api/options/{type}', [CategoryController::class, 'getOptions'])->name('api.options');
});

    // === CATEGORY ALIASES FOR BACKWARD COMPATIBILITY ===
    // Redirect old category routes to unified category management
    Route::prefix('kategori-tematik')->name('kategori-tematik.')->group(function () {
        Route::get('/', function() {
            return redirect()->route('categories.index') . '?type=tematik';
        })->name('index');
        Route::get('/create', function() {
            return redirect()->route('categories.create') . '?type=tematik';
        })->name('create');
        Route::post('/', function() {
            return redirect()->route('categories.store');
        })->name('store');
        Route::get('/{id}/edit', function($id) {
            return redirect()->route('categories.edit', $id);
        })->name('edit');
        Route::put('/{id}', function($id) {
            return redirect()->route('categories.update', $id);
        })->name('update');
        Route::delete('/{id}', function($id) {
            return redirect()->route('categories.destroy', $id);
        })->name('destroy');
    });

    Route::prefix('kategori-usulan-musrenbang')->name('kategori-usulan-musrenbang.')->group(function () {
        Route::get('/', function() {
            return redirect()->route('categories.index') . '?type=usulan_musrenbang';
        })->name('index');
        Route::get('/create', function() {
            return redirect()->route('categories.create') . '?type=usulan_musrenbang';
        })->name('create');
        Route::post('/', function() {
            return redirect()->route('categories.store');
        })->name('store');
        Route::get('/{id}/edit', function($id) {
            return redirect()->route('categories.edit', $id);
        })->name('edit');
        Route::put('/{id}', function($id) {
            return redirect()->route('categories.update', $id);
        })->name('update');
        Route::delete('/{id}', function($id) {
            return redirect()->route('categories.destroy', $id);
        })->name('destroy');
    });

    Route::prefix('kategori-pokir-dprd')->name('kategori-pokir-dprd.')->group(function () {
        Route::get('/', function() {
            return redirect()->route('categories.index') . '?type=pokir_dprd';
        })->name('index');
        Route::get('/create', function() {
            return redirect()->route('categories.create') . '?type=pokir_dprd';
        })->name('create');
        Route::post('/', function() {
            return redirect()->route('categories.store');
        })->name('store');
        Route::get('/{id}/edit', function($id) {
            return redirect()->route('categories.edit', $id);
        })->name('edit');
        Route::put('/{id}', function($id) {
            return redirect()->route('categories.update', $id);
        })->name('update');
        Route::delete('/{id}', function($id) {
            return redirect()->route('categories.destroy', $id);
        })->name('destroy');
    });

    Route::prefix('kategori-psd')->name('kategori-psd.')->group(function () {
        Route::get('/', function() {
            return redirect()->route('categories.index') . '?type=psd';
        })->name('index');
        Route::get('/create', function() {
            return redirect()->route('categories.create') . '?type=psd';
        })->name('create');
        Route::post('/', function() {
            return redirect()->route('categories.store');
        })->name('store');
        Route::get('/{id}/edit', function($id) {
            return redirect()->route('categories.edit', $id);
        })->name('edit');
        Route::put('/{id}', function($id) {
            return redirect()->route('categories.update', $id);
        })->name('update');
        Route::delete('/{id}', function($id) {
            return redirect()->route('categories.destroy', $id);
        })->name('destroy');
    });

    Route::prefix('kategori-psn')->name('kategori-psn.')->group(function () {
        Route::get('/', function() {
            return redirect()->route('categories.index') . '?type=psn';
        })->name('index');
        Route::get('/create', function() {
            return redirect()->route('categories.create') . '?type=psn';
        })->name('create');
        Route::post('/', function() {
            return redirect()->route('categories.store');
        })->name('store');
        Route::get('/{id}/edit', function($id) {
            return redirect()->route('categories.edit', $id);
        })->name('edit');
        Route::put('/{id}', function($id) {
            return redirect()->route('categories.update', $id);
        })->name('update');
        Route::delete('/{id}', function($id) {
            return redirect()->route('categories.destroy', $id);
        })->name('destroy');
    });

    // === DOCUMENT UPLOAD ROUTES ===
    Route::prefix('upload-dokumen')->name('dokumen.')->group(function () {
        Route::get('/', [DokumenController::class, 'index'])->name('index');
        Route::post('/', [DokumenController::class, 'store'])->name('store');
        Route::put('/{id}', [DokumenController::class, 'update'])->where('id', '[0-9]+')->name('update');
        Route::delete('/{id}', [DokumenController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
    });

    // === GENERAL API ROUTES ===
    Route::prefix('api')->name('api.')->group(function () {
        // GeoJSON dengan filter dinamis
        Route::get('/geojson', [DataSpatialController::class, 'geojson'])->name('geojson');
        
        // Statistics dengan filter dinamis
        Route::get('/statistics', [DataSpatialController::class, 'getStatistics'])->name('statistics');
        
        // DBF utilities
        Route::get('/dbf-columns', [DataSpatialController::class, 'getDbfColumns'])->name('dbf.columns');
        Route::get('/dbf-columns/{column}/values', [DataSpatialController::class, 'getDbfColumnValues'])->name('dbf.values');
        
        // Categories
        Route::get('/categories', [DataSpatialController::class, 'getCategories'])->name('categories');
        
        // Debug
        Route::post('/debug/shapefile', [DataSpatialController::class, 'debugShapefile'])->name('debug.shapefile');
        Route::post('/debug/kmz', [DataSpatialController::class, 'debugKmz'])->name('debug.kmz');
        
//         Route::get('/data-spatial/{uuid}/details', function($uuid) {
//     $data = \App\Models\DataSpatial::with('kategori')->where('uuid', $uuid)->first();
//     return response()->json([
//         'success' => $data ? true : false,
//         'data' => $data,
//         'message' => $data ? 'Data ditemukan' : 'Data tidak ditemukan'
//     ]);
// })->name('data-spatial.details');

    });

    // === COMING SOON PAGE ===
    Route::get('/coming-soon', function () {
        return view('backend.cooming_soon');
    })->name('cooming_soon');
});

/*
|--------------------------------------------------------------------------
| Project Feedback Routes
|--------------------------------------------------------------------------
*/

Route::prefix('dashboard')->middleware(['auth'])->group(function() {

   // Project Feedback Routes dengan support untuk type dan sub_type filtering
    Route::prefix('project-feedbacks')->name('project-feedbacks.')->group(function () {
        // Index dengan support query parameters type dan sub_type
        Route::get('/', [ProjectFeedbackController::class, 'index'])->name('index');
        
        // CRUD Operations
        Route::post('/', [ProjectFeedbackController::class, 'store'])->name('store');
        Route::get('/{id}', [ProjectFeedbackController::class, 'show'])->name('show');
        Route::put('/{id}', [ProjectFeedbackController::class, 'update'])->name('update');
        Route::delete('/{id}', [ProjectFeedbackController::class, 'destroy'])->name('destroy');
        
        // Admin Response - Support both PUT and POST methods
        Route::put('/{id}/respond', [ProjectFeedbackController::class, 'respond'])->name('respond');
        Route::post('/{id}/respond', [ProjectFeedbackController::class, 'respond'])->name('respond.post');
    });

   
 // Resource Routes
    Route::resource('roles', RoleController::class);
    Route::resource('kategori-aspirasi', KategoriAspirasiController::class);
    // Routes untuk Aspirasi
    // Routes untuk Aspirasi
    Route::resource('aspirasi', AspirasiController::class);
    Route::put('aspirasi/{aspirasi}', [AspirasiController::class, 'updateStatus'])->name('aspirasi.updateStatus');
    Route::get('aspirasi/{aspirasi}/download/{index}', [AspirasiController::class, 'downloadLampiran'])->name('aspirasi.downloadLampiran');    
    Route::get('api/categories-by-opd/{opd}', [KategoriAspirasiController::class, 'getByOpd'])->name('api.categories-by-opd');
    // Routes untuk Kategori Aspirasi
    Route::resource('kategori-aspirasi', KategoriAspirasiController::class);
    Route::get('kategori-aspirasi-generate-kode', [KategoriAspirasiController::class, 'generateKode'])->name('kategori-aspirasi.generateKode');
    Route::get('kategori-aspirasi-api-options', [KategoriAspirasiController::class, 'apiOptions'])->name('kategori-aspirasi.apiOptions');
    // Route::get('kategori-aspirasi/{id}', [KategoriAspirasiController::class, 'show'])->name('kategori-aspirasi.show');

    Route::resource('opd', OpdController::class);
    Route::get('/opd/list', [OpdController::class, 'getOpdList'])->name('opd.list');
    Route::get('/opd/search', [OpdController::class, 'search'])->name('opd.search');
    Route::get('/opd/stats', [OpdController::class, 'getStats'])->name('opd.stats');

});
