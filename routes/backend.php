<?php

use App\Http\Controllers\AspirasiController;
use App\Http\Controllers\LayerWebController;
use App\Http\Controllers\FeatureWebController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DataSpatialController;
use App\Http\Controllers\KategoriAspirasiController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\ProjectFeedbackController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\PublicationDownloadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\VisitorsController;
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
| Roles: super-admin, admin-bappeda, admin-opd
|
*/

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/



Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
// Dashboard API routes untuk AJAX calls
Route::prefix('dashboard/api')->name('dashboard.api.')->group(function () {
    Route::get('/years', [DashboardController::class, 'getAvailableYears'])->name('years');
    Route::get('/categories', [DashboardController::class, 'getCategories'])->name('categories');
    Route::get('/statistics', [DashboardController::class, 'getStatistics'])->name('statistics');
    Route::get('/top-categories', [DashboardController::class, 'getTopCategories'])->name('top-categories');
    Route::get('/response-time', [DashboardController::class, 'getResponseTimeAnalytics'])->name('response-time');
    Route::get('/stats', [DashboardController::class, 'getDashboardStats'])->name('stats');
});

// Statistics page route
Route::get('/dashboard/statistics', [DashboardController::class, 'statistics'])->name('dashboard.statistics');

/*
|--------------------------------------------------------------------------
| Profile Management
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Add this to your routes file
    Route::get('dashboard/visitor-statistics', [DashboardController::class, 'getVisitorStatistics'])->name('dashboard.visitor.statistics');
});

/*
|--------------------------------------------------------------------------
| Dashboard Panel Routes
|--------------------------------------------------------------------------
*/

Route::prefix('dashboard')->middleware(['auth'])->group(function () {
    // Visitor Analytics routes
    Route::get('/visitors/export', [VisitorsController::class, 'export'])->name('visitors.export');
    Route::post('/visitors/analytics', [VisitorsController::class, 'analytics'])->name('visitors.analytics');
    Route::delete('/visitors/bulk-destroy', [VisitorsController::class, 'bulkDestroy'])->name('visitors.bulk-destroy');

    // Resource routes for visitors
    Route::resource('visitors', VisitorsController::class)->only(['index', 'show', 'destroy']);
    /*
    |--------------------------------------------------------------------------
    | Unified Data Spatial Management
    |--------------------------------------------------------------------------
    */

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
        Route::get('/{uuid}/details', function ($uuid) {
            $data = \App\Models\DataSpatial::with('kategori')->where('uuid', $uuid)->first();
            return response()->json([
                'success' => $data ? true : false,
                'data' => $data,
                'message' => $data ? 'Data found' : 'Data not found'
            ]);
        })->name('details');
    });

    /*
    |--------------------------------------------------------------------------
    | Peta RPJMD (Tematik) Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('tematik')->name('tematik.')->group(function () {
        Route::get('/', [DataSpatialController::class, 'indextematik'])->name('index');
        Route::get('/create', function () {
            return redirect()->route('data-spatial.create') . '?type=tematik';
        })->name('create');
        Route::get('/{uuid}/edit', function ($id) {
            return redirect()->route('data-spatial.edit', $id);
        })->name('edit');
        Route::put('/{uuid}', function ($id) {
            return redirect()->route('data-spatial.update', $id);
        })->name('update');
        Route::delete('/{uuid}', function ($id) {
            return redirect()->route('data-spatial.destroy', $id);
        })->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Usulan Musrenbang Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('usulan-musrenbang')->name('usulan-musrenbang.')->group(function () {
        Route::get('/', [DataSpatialController::class, 'indexUsulanmusrenbang'])->name('index');
        Route::get('/create', function () {
            return redirect()->route('data-spatial.create') . '?type=usulan_musrenbang';
        })->name('create');
        Route::get('/{uuid}/edit', function ($id) {
            return redirect()->route('data-spatial.edit', $id);
        })->name('edit');
        Route::put('/{uuid}', function ($id) {
            return redirect()->route('data-spatial.update', $id);
        })->name('update');
        Route::delete('/{uuid}', function ($id) {
            return redirect()->route('data-spatial.destroy', $id);
        })->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Pokir DPRD Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('pokir-dprd')->name('pokir-dprd.')->group(function () {
        Route::get('/', [DataSpatialController::class, 'indexPokirDprd'])->name('index');
        Route::get('/create', function () {
            return redirect()->route('data-spatial.create') . '?type=pokir_dprd';
        })->name('create');
        Route::get('/{uuid}/edit', function ($id) {
            return redirect()->route('data-spatial.edit', $id);
        })->name('edit');
        Route::put('/{uuid}', function ($id) {
            return redirect()->route('data-spatial.update', $id);
        })->name('update');
        Route::delete('/{uuid}', function ($id) {
            return redirect()->route('data-spatial.destroy', $id);
        })->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Proyek Strategis Daerah Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('proyek-strategis-daerah')->name('psd.')->group(function () {
        Route::get('/', [DataSpatialController::class, 'indexProyekStrategisDaerah'])->name('index');
        Route::get('/create', function () {
            return redirect()->route('data-spatial.create') . '?type=proyek_strategis&sub_type=psd';
        })->name('create');

        // Routes berdasarkan tahun
        Route::get('/tahun/{year}', [DataSpatialController::class, 'indexProyekStrategisDaerah'])->name('tahun.show');
        Route::get('/tahun/{year}/create', function ($year) {
            return redirect()->route('data-spatial.create') . "?type=proyek_strategis&sub_type=psd&year={$year}";
        })->name('tahun.create');
    });

    /*
    |--------------------------------------------------------------------------
    | Proyek Strategis Nasional Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('proyek-strategis-nasional')->name('psn.')->group(function () {
        Route::get('/', [DataSpatialController::class, 'indexProyekStrategisNasional'])->name('index');
        Route::get('/create', function () {
            return redirect()->route('data-spatial.create') . '?type=proyek_strategis&sub_type=psn';
        })->name('create');

        // Routes berdasarkan tahun
        Route::get('/tahun/{year}', [DataSpatialController::class, 'indexProyekStrategisNasional'])->name('tahun.show');
        Route::get('/tahun/{year}/create', function ($year) {
            return redirect()->route('data-spatial.create') . "?type=proyek_strategis&sub_type=psn&year={$year}";
        })->name('tahun.create');
    });

    /*
    |--------------------------------------------------------------------------
    | Category Management
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Document Upload Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'role:super-admin,admin-bappeda'])->prefix('upload-dokumen')->name('dokumen.')->group(function () {
        Route::get('/', [DokumenController::class, 'index'])->name('index');
        Route::post('/', [DokumenController::class, 'store'])->name('store');
        Route::put('/{id}', [DokumenController::class, 'update'])->where('id', '[0-9]+')->name('update');
        Route::delete('/{id}', [DokumenController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Project Feedback Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('project-feedbacks')->name('project-feedbacks.')->group(function () {
        Route::get('/', [ProjectFeedbackController::class, 'index'])->name('index');
        Route::post('/', [ProjectFeedbackController::class, 'store'])->name('store');
        Route::get('/{id}', [ProjectFeedbackController::class, 'show'])->name('show');
        Route::put('/{id}', [ProjectFeedbackController::class, 'update'])->name('update');
        Route::delete('/{id}', [ProjectFeedbackController::class, 'destroy'])->name('destroy');

        // Admin Response
        Route::put('/{id}/respond', [ProjectFeedbackController::class, 'respond'])->name('respond');
        Route::post('/{id}/respond', [ProjectFeedbackController::class, 'respond'])->name('respond.post');
        // Route untuk update OPD feedback
        Route::put('/feedback/{feedback}/update-opd', [ProjectFeedbackController::class, 'updateOpd'])->name('update-opd');
    });

    /*
    |--------------------------------------------------------------------------
    | Resource Routes
    |--------------------------------------------------------------------------
    */

    // Aspirasi Management
    Route::resource('aspirasi', AspirasiController::class);
    Route::put('aspirasi/{aspirasi}', [AspirasiController::class, 'updateStatus'])->name('aspirasi.updateStatus');
    // Routes untuk lampiran
    Route::get('/aspirasi/{aspirasi}/lampiran/{index}/download', [AspirasiController::class, 'downloadLampiran'])
        ->name('aspirasi.downloadLampiran')
        ->where('index', '[0-9]+');

    Route::get('/aspirasi/{aspirasi}/lampiran/{index}/preview', [AspirasiController::class, 'previewLampiran'])
        ->name('aspirasi.previewLampiran')
        ->where('index', '[0-9]+');

    Route::get('/aspirasi/{aspirasi}/lampiran/{index}/info', [AspirasiController::class, 'getFileInfo'])
        ->name('aspirasi.getFileInfo')
        ->where('index', '[0-9]+');

    Route::get('/aspirasi/quick-export/data', [AspirasiController::class, 'export'])->name('aspirasi.export');
    Route::post('/aspirasi/export-filtered', [AspirasiController::class, 'exportFiltered'])->name('aspirasi.export-filtered');
    Route::post('/aspirasi/preview-export', [AspirasiController::class, 'previewExport'])->name('aspirasi.preview-export');


    // Bulk operations - also before resource routes
    Route::delete('/bulk-aspirasi-destroy', [AspirasiController::class, 'bulkDestroy'])->name('aspirasi.bulk-destroy');



    // Kategori Aspirasi Management
    Route::middleware(['auth', 'role:super-admin,admin-bappeda'])->group(function () {

        // Kategori Aspirasi Management
        Route::resource('kategori-aspirasi', KategoriAspirasiController::class);
        Route::get('kategori-aspirasi-generate-kode', [KategoriAspirasiController::class, 'generateKode'])->name('kategori-aspirasi.generateKode');
        Route::get('kategori-aspirasi-api-options', [KategoriAspirasiController::class, 'apiOptions'])->name('kategori-aspirasi.apiOptions');

        // OPD Management
        Route::resource('opd', OpdController::class);
        Route::get('/opd/list', [OpdController::class, 'getOpdList'])->name('opd.list');
        Route::get('/opd/search', [OpdController::class, 'search'])->name('opd.search');
        Route::get('/opd/stats', [OpdController::class, 'getStats'])->name('opd.stats');

        // User Management
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });


    /*
    |--------------------------------------------------------------------------
    | General API Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('api')->name('api.')->group(function () {

        // Categories
        Route::get('/categories', [DataSpatialController::class, 'getCategories'])->name('categories');
        Route::get('/categories-by-opd/{opd}', [KategoriAspirasiController::class, 'getByOpd'])->name('categories-by-opd');

        // Data Spatial Details
        Route::get('/data-spatial/{uuid}/details', function ($uuid) {
            $data = \App\Models\DataSpatial::with('kategori')->where('uuid', $uuid)->first();
            return response()->json([
                'success' => $data ? true : false,
                'data' => $data,
                'message' => $data ? 'Data ditemukan' : 'Data tidak ditemukan'
            ]);
        })->name('data-spatial.details');
    });


    /*
    |--------------------------------------------------------------------------
    | publications Routes
    |--------------------------------------------------------------------------
    */
    // routes/web.php

    Route::prefix('publications')->name('publications.')->group(function () {
        // List all publications for admin
        Route::get('/', [PublicationController::class, 'adminIndex'])->name('index');

        // Create new publication
        Route::get('/create', [PublicationController::class, 'create'])->name('create');
        Route::post('/', [PublicationController::class, 'store'])->name('store');

        // Edit publication
        Route::get('/{publication}/edit', [PublicationController::class, 'edit'])->name('edit');
        Route::put('/{publication}', [PublicationController::class, 'update'])->name('update');

        // Delete publication
        Route::delete('/{publication}', [PublicationController::class, 'destroy'])->name('destroy');

        // Download and Preview - FIXED
        Route::get('/{publication}/download', [PublicationController::class, 'download'])->name('download');
        Route::get('/{publication}/preview', [PublicationController::class, 'preview'])->name('preview');

        // Toggle publication status
        Route::patch('/{publication}/toggle-status', [PublicationController::class, 'toggleStatus'])->name('toggle-status');

        // Bulk actions
        Route::post('/bulk-delete', [PublicationController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/bulk-toggle-status', [PublicationController::class, 'bulkToggleStatus'])->name('bulk-toggle-status');

        Route::prefix('downloads')->name('downloads.')->group(function () {
            Route::get('/', [PublicationDownloadController::class, 'index'])->name('index');
            Route::get('/publication/{publication}', [PublicationDownloadController::class, 'show'])->name('show');
            Route::get('/analytics', [PublicationDownloadController::class, 'analytics'])->name('analytics');
            Route::get('/export', [PublicationDownloadController::class, 'export'])->name('export');
            Route::delete('/{download}', [PublicationDownloadController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-destroy', [PublicationDownloadController::class, 'bulkDestroy'])->name('bulk-destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Utility Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/coming-soon', function () {
        return view('backend.cooming_soon');
    })->name('coming_soon');

    /*
    |--------------------------------------------------------------------------
    | Layer & Feature Management (Arsitektur Baru)
    |--------------------------------------------------------------------------
    */
    Route::prefix('layers')->name('layers.')->group(function () {
        Route::get('/', [LayerWebController::class, 'index'])->name('index');
        Route::get('/data', [LayerWebController::class, 'getData'])->name('data');
        Route::get('/create', [LayerWebController::class, 'create'])->name('create');
        Route::post('/', [LayerWebController::class, 'store'])->name('store');
        Route::get('/{layer}/edit', [LayerWebController::class, 'edit'])->name('edit');
        Route::put('/{layer}', [LayerWebController::class, 'update'])->name('update');
        Route::delete('/{layer}', [LayerWebController::class, 'destroy'])->name('destroy');
        Route::patch('/{layer}/toggle-active', [LayerWebController::class, 'toggleActive'])->name('toggle-active');
        Route::patch('/{layer}/move', [LayerWebController::class, 'move'])->name('move');

        Route::prefix('{layer}/features')->name('features.')->group(function () {
            Route::get('/', [FeatureWebController::class, 'index'])->name('index');
            Route::get('/map-data', [FeatureWebController::class, 'mapData'])->name('map-data');
            Route::get('/create', [FeatureWebController::class, 'create'])->name('create');
            Route::post('/', [FeatureWebController::class, 'store'])->name('store');
            Route::get('/{feature}', [FeatureWebController::class, 'show'])->name('show');
            Route::get('/{feature}/edit', [FeatureWebController::class, 'edit'])->name('edit');
            Route::put('/{feature}', [FeatureWebController::class, 'update'])->name('update');
            Route::delete('/{feature}', [FeatureWebController::class, 'destroy'])->name('destroy');
            Route::post('/{feature}/images', [FeatureWebController::class, 'uploadImage'])->name('images.store');
            Route::delete('/{feature}/images/{image}', [FeatureWebController::class, 'deleteImage'])->name('images.destroy');
        });
    });
});
Route::get('/coming-soon', function () {
    return view('coming_soon');
})->name('coming_soon_public');
