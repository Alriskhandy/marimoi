<?php

use App\Http\Controllers\AspirasiController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataSpatialController;
use App\Http\Controllers\ExecutiveDashboardController;
use App\Http\Controllers\KategoriAspirasiController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\MapTypeController;
use App\Http\Controllers\MetadataDefinitionController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\PembangunanDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectFeedbackController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\PublicationDownloadController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SpatialFeedbackController;
use App\Http\Controllers\SpatialLayerController;
use App\Http\Controllers\SpatialLayerFeatureController;
use App\Http\Controllers\SpatialLayerMetadataController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorsController;
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
| Roles: super-admin, admin-bappeda, admin-opd
|
*/

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:dashboard.view'])
    ->name('dashboard');
// Dashboard API routes untuk AJAX calls
Route::prefix('dashboard/api')->name('dashboard.api.')->group(function () {
    Route::get('/years', [DashboardController::class, 'getAvailableYears'])->name('years')->middleware('permission:dashboard.view');
    Route::get('/categories', [DashboardController::class, 'getCategories'])->name('categories')->middleware('permission:dashboard.view');
    Route::get('/statistics', [DashboardController::class, 'getStatistics'])->name('statistics')->middleware('permission:dashboard.view');
    Route::get('/top-categories', [DashboardController::class, 'getTopCategories'])->name('top-categories')->middleware('permission:dashboard.view');
    Route::get('/response-time', [DashboardController::class, 'getResponseTimeAnalytics'])->name('response-time')->middleware('permission:dashboard.view');
    Route::get('/stats', [DashboardController::class, 'getDashboardStats'])->name('stats')->middleware('permission:dashboard.view');
});

// Statistics page route
Route::get('/dashboard/statistics', [DashboardController::class, 'statistics'])->name('dashboard.statistics')->middleware('permission:dashboard.view');

// Dashboard eksekutif berbasis development_projects (Prioritas 6 database V2) — endpoint
// data JSON, terpisah dari dashboard/pembangunan (masih berbasis data_spatial) yang sudah ada.
Route::prefix('dashboard/api/eksekutif')->name('dashboard.api.eksekutif.')->middleware('permission:dashboard.view')->group(function () {
    Route::get('/summary', [ExecutiveDashboardController::class, 'summary'])->name('summary');
    Route::get('/sektor', [ExecutiveDashboardController::class, 'bySector'])->name('sektor');
    Route::get('/wilayah', [ExecutiveDashboardController::class, 'byRegion'])->name('wilayah');
    Route::get('/tren', [ExecutiveDashboardController::class, 'trend'])->name('tren');
});

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
    Route::get('dashboard/visitor-statistics', [DashboardController::class, 'getVisitorStatistics'])->name('dashboard.visitor.statistics')->middleware('permission:dashboard.view');
});

/*
|--------------------------------------------------------------------------
| Dashboard Panel Routes
|--------------------------------------------------------------------------
*/

Route::prefix('dashboard')->middleware(['auth'])->group(function () {
    // Visitor Analytics routes
    Route::get('/visitors/export', [VisitorsController::class, 'export'])->name('visitors.export')->middleware('permission:visitors.view');
    Route::post('/visitors/analytics', [VisitorsController::class, 'analytics'])->name('visitors.analytics')->middleware('permission:visitors.view');
    Route::delete('/visitors/bulk-destroy', [VisitorsController::class, 'bulkDestroy'])->name('visitors.bulk-destroy')->middleware('permission:visitors.delete');

    // Resource routes for visitors
    Route::resource('visitors', VisitorsController::class)->only(['index', 'show'])->middleware('permission:visitors.view');
    Route::resource('visitors', VisitorsController::class)->only(['destroy'])->middleware('permission:visitors.delete');
    /*
    |--------------------------------------------------------------------------
    | Unified Data Spatial Management
    |--------------------------------------------------------------------------
    */

    Route::prefix('data-spatial')->name('data-spatial.')->group(function () {
        Route::get('/', [DataSpatialController::class, 'index'])->name('index')->middleware('permission:data-spatial.view');
        Route::get('/peta', [DataSpatialController::class, 'map'])->name('map')->middleware('permission:data-spatial.view');
        Route::get('/geojson', [DataSpatialController::class, 'geojson'])->name('geojson')->middleware('permission:data-spatial.view');
        Route::get('/geojson-version', [DataSpatialController::class, 'geojsonVersion'])->name('geojson-version')->middleware('permission:data-spatial.view');
        Route::post('/export-drawings', [DataSpatialController::class, 'exportDrawings'])->name('export-drawings')->middleware('permission:data-spatial.view');
        Route::get('/create', [DataSpatialController::class, 'create'])->name('create')->middleware('permission:data-spatial.create');
        Route::post('/store', [DataSpatialController::class, 'store'])->name('store')->middleware('permission:data-spatial.create');
        Route::get('/{uuid}/edit', [DataSpatialController::class, 'edit'])->name('edit')->middleware('permission:data-spatial.edit');
        Route::put('/{uuid}', [DataSpatialController::class, 'update'])->name('update')->middleware('permission:data-spatial.edit');
        Route::delete('/{uuid}', [DataSpatialController::class, 'destroy'])->name('destroy')->middleware('permission:data-spatial.delete');
        Route::post('/bulk-update-category', [DataSpatialController::class, 'bulkUpdateCategory'])->name('bulk-update-category')->middleware('permission:data-spatial.edit');
        Route::post('/bulk-update-attribute', [DataSpatialController::class, 'bulkUpdateAttribute'])->name('bulk-update-attribute')->middleware('permission:data-spatial.edit');

        // Debug routes for file uploads
        Route::post('/debug/shapefile', [DataSpatialController::class, 'debugShapefile'])->name('debug.shapefile')->middleware('permission:data-spatial.create');
        Route::post('/debug/kmz', [DataSpatialController::class, 'debugKmz'])->name('debug.kmz')->middleware('permission:data-spatial.create');

        // Detail endpoint for modal
        Route::get('/{uuid}/details', function ($uuid) {
            $data = DataSpatial::with(['kategori', 'opdPengelola'])->where('uuid', $uuid)->first();

            return response()->json([
                'success' => $data ? true : false,
                'data' => $data,
                'message' => $data ? 'Data found' : 'Data not found',
            ]);
        })->name('details')->middleware('permission:data-spatial.view');
    });

    /*
    |--------------------------------------------------------------------------
    | Peta RPJMD (Tematik) Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('tematik')->name('tematik.')->group(function () {
        Route::get('/', [DataSpatialController::class, 'indextematik'])->name('index')->middleware('permission:data-spatial.view');
        Route::get('/create', function () {
            return redirect()->route('data-spatial.create').'?type=tematik';
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
    | Category Management
    |--------------------------------------------------------------------------
    */

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index')->middleware('permission:categories.view');
        Route::post('/store', [CategoryController::class, 'store'])->name('store')->middleware('permission:categories.create');
        Route::put('/{id}', [CategoryController::class, 'update'])->name('update')->middleware('permission:categories.edit');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy')->middleware('permission:categories.delete');

        // Metadata layer skema baru (spatial_layer_metadata) — docs/marimoi v2/
        // 04_implementation/11-plan-dashboard-skema-baru.md Bagian A.
        Route::get('/{id}/metadata', [SpatialLayerMetadataController::class, 'edit'])->name('metadata.edit')->middleware('permission:categories.edit');
        Route::put('/{id}/metadata', [SpatialLayerMetadataController::class, 'update'])->name('metadata.update')->middleware('permission:categories.edit');

        // API cascading select (3 level) dipakai index.blade.php — satu-satunya
        // endpoint API Category yang benar-benar dikonsumsi JS (lihat Category
        // model/controller untuk method lain yang sudah tidak diroutekan: sempat
        // ada getByType/getTree tapi tidak dipanggil di mana pun).
        Route::get('/api/options/{type}', [CategoryController::class, 'getOptions'])->name('api.options')->middleware('permission:categories.view');
    });

    /*
    |--------------------------------------------------------------------------
    | Map Type Management (jenis peta, master data spatial_layers.map_type_id)
    |--------------------------------------------------------------------------
    */

    Route::resource('map-types', MapTypeController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('permission:map-types.manage');

    Route::get('metadata-definitions/search', [MetadataDefinitionController::class, 'search'])
        ->name('metadata-definitions.search')
        ->middleware('permission:map-types.manage');

    /*
    |--------------------------------------------------------------------------
    | Daftar Layer & Data (docs/marimoi v2/04_implementation/
    | 12-implementasi-perbaikan-pemetaan.md Bagian 3.2)
    |--------------------------------------------------------------------------
    */

    Route::prefix('spatial-layers')->name('spatial-layers.')->group(function () {
        Route::get('/', [SpatialLayerController::class, 'index'])->name('index')->middleware('permission:spatial-layers.view');
        Route::get('/create', [SpatialLayerController::class, 'create'])->name('create')->middleware('permission:spatial-layers.create');
        Route::post('/', [SpatialLayerController::class, 'store'])->name('store')->middleware('permission:spatial-layers.create');
        Route::put('/bulk-update-map-type', [SpatialLayerController::class, 'bulkUpdateMapType'])->name('bulk-update-map-type')->middleware('permission:spatial-layers.edit');
        Route::get('/{spatialLayer}', [SpatialLayerController::class, 'show'])->name('show')->middleware('permission:spatial-layers.view');
        Route::put('/{spatialLayer}', [SpatialLayerController::class, 'update'])->name('update')->middleware('permission:spatial-layers.edit');
        Route::delete('/{spatialLayer}', [SpatialLayerController::class, 'destroy'])->name('destroy')->middleware('permission:spatial-layers.delete');

        Route::prefix('{spatialLayer}/features')->name('features.')->group(function () {
            Route::get('/create', [SpatialLayerFeatureController::class, 'create'])->name('create')->middleware('permission:spatial-layers.create');
            Route::post('/', [SpatialLayerFeatureController::class, 'store'])->name('store')->middleware('permission:spatial-layers.create');
            Route::get('/{feature}/edit', [SpatialLayerFeatureController::class, 'edit'])->name('edit')->middleware('permission:spatial-layers.edit');
            Route::put('/{feature}', [SpatialLayerFeatureController::class, 'update'])->name('update')->middleware('permission:spatial-layers.edit');
            Route::delete('/{feature}', [SpatialLayerFeatureController::class, 'destroy'])->name('destroy')->middleware('permission:spatial-layers.delete');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Feedback Pemetaan (docs/marimoi v2/04_implementation/
    | 12-implementasi-perbaikan-pemetaan.md Bagian 3.3)
    |--------------------------------------------------------------------------
    */

    Route::prefix('spatial-feedbacks')->name('spatial-feedbacks.')->group(function () {
        Route::get('/', [SpatialFeedbackController::class, 'index'])->name('index')->middleware('permission:spatial-feedbacks.view');
        Route::put('/{spatialFeedback}/respond', [SpatialFeedbackController::class, 'respond'])->name('respond')->middleware('permission:spatial-feedbacks.respond');
        Route::delete('/{spatialFeedback}', [SpatialFeedbackController::class, 'destroy'])->name('destroy')->middleware('permission:spatial-feedbacks.delete');
    });

    /*
    |--------------------------------------------------------------------------
    | Project Feedback Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('project-feedbacks')->name('project-feedbacks.')->group(function () {
        Route::get('/', [ProjectFeedbackController::class, 'index'])->name('index')->middleware('permission:project-feedbacks.view');
        Route::post('/', [ProjectFeedbackController::class, 'store'])->name('store')->middleware('permission:project-feedbacks.respond');
        Route::get('/{id}', [ProjectFeedbackController::class, 'show'])->name('show')->middleware('permission:project-feedbacks.view');
        Route::put('/{id}', [ProjectFeedbackController::class, 'update'])->name('update')->middleware('permission:project-feedbacks.respond');
        Route::delete('/{id}', [ProjectFeedbackController::class, 'destroy'])->name('destroy')->middleware('permission:project-feedbacks.delete');

        // Admin Response
        Route::put('/{id}/respond', [ProjectFeedbackController::class, 'respond'])->name('respond')->middleware('permission:project-feedbacks.respond');
        Route::post('/{id}/respond', [ProjectFeedbackController::class, 'respond'])->name('respond.post')->middleware('permission:project-feedbacks.respond');
        // Route untuk update OPD feedback
        Route::put('/feedback/{feedback}/update-opd', [ProjectFeedbackController::class, 'updateOpd'])->name('update-opd')->middleware('permission:project-feedbacks.respond');
    });

    /*
    |--------------------------------------------------------------------------
    | Dashboard Pembangunan
    |--------------------------------------------------------------------------
    */

    Route::get('/pembangunan', [PembangunanDashboardController::class, 'index'])
        ->name('dashboard.pembangunan')
        ->middleware('permission:project-progress.view');

    /*
    |--------------------------------------------------------------------------
    | Resource Routes
    |--------------------------------------------------------------------------
    */

    // Aspirasi Management
    Route::resource('aspirasi', AspirasiController::class)->only(['index', 'show'])->middleware('permission:aspirasi.view');
    Route::resource('aspirasi', AspirasiController::class)->only(['create', 'store'])->middleware('permission:aspirasi.create');
    Route::resource('aspirasi', AspirasiController::class)->only(['edit', 'update'])->middleware('permission:aspirasi.edit');
    Route::resource('aspirasi', AspirasiController::class)->only(['destroy'])->middleware('permission:aspirasi.delete');
    Route::put('aspirasi/{aspirasi}', [AspirasiController::class, 'updateStatus'])->name('aspirasi.updateStatus')->middleware('permission:aspirasi.edit');
    // Routes untuk lampiran
    Route::get('/aspirasi/{aspirasi}/lampiran/{index}/download', [AspirasiController::class, 'downloadLampiran'])
        ->name('aspirasi.downloadLampiran')
        ->where('index', '[0-9]+')
        ->middleware('permission:aspirasi.view');

    Route::get('/aspirasi/{aspirasi}/lampiran/{index}/preview', [AspirasiController::class, 'previewLampiran'])
        ->name('aspirasi.previewLampiran')
        ->where('index', '[0-9]+')
        ->middleware('permission:aspirasi.view');

    Route::get('/aspirasi/{aspirasi}/lampiran/{index}/info', [AspirasiController::class, 'getFileInfo'])
        ->name('aspirasi.getFileInfo')
        ->where('index', '[0-9]+')
        ->middleware('permission:aspirasi.view');

    Route::get('/aspirasi/quick-export/data', [AspirasiController::class, 'export'])->name('aspirasi.export')->middleware('permission:aspirasi.export');
    Route::post('/aspirasi/export-filtered', [AspirasiController::class, 'exportFiltered'])->name('aspirasi.export-filtered')->middleware('permission:aspirasi.export');
    Route::post('/aspirasi/preview-export', [AspirasiController::class, 'previewExport'])->name('aspirasi.preview-export')->middleware('permission:aspirasi.export');

    // Bulk operations - also before resource routes
    Route::delete('/bulk-aspirasi-destroy', [AspirasiController::class, 'bulkDestroy'])->name('aspirasi.bulk-destroy')->middleware('permission:aspirasi.delete');

    // Kategori Aspirasi Management
    Route::middleware(['auth'])->group(function () {

        // Kategori Aspirasi Management
        Route::resource('kategori-aspirasi', KategoriAspirasiController::class)->only(['index', 'show'])->middleware('permission:kategori-aspirasi.view');
        Route::resource('kategori-aspirasi', KategoriAspirasiController::class)->only(['create', 'store'])->middleware('permission:kategori-aspirasi.create');
        Route::resource('kategori-aspirasi', KategoriAspirasiController::class)->only(['edit', 'update'])->middleware('permission:kategori-aspirasi.edit');
        Route::resource('kategori-aspirasi', KategoriAspirasiController::class)->only(['destroy'])->middleware('permission:kategori-aspirasi.delete');
        Route::get('kategori-aspirasi-generate-kode', [KategoriAspirasiController::class, 'generateKode'])->name('kategori-aspirasi.generateKode')->middleware('permission:kategori-aspirasi.create');
        Route::get('kategori-aspirasi-api-options', [KategoriAspirasiController::class, 'apiOptions'])->name('kategori-aspirasi.apiOptions')->middleware('permission:kategori-aspirasi.view');

        // OPD Management
        Route::resource('opd', OpdController::class)->only(['index', 'show'])->middleware('permission:opd.view');
        Route::resource('opd', OpdController::class)->only(['create', 'store'])->middleware('permission:opd.create');
        Route::resource('opd', OpdController::class)->only(['edit', 'update'])->middleware('permission:opd.edit');
        Route::resource('opd', OpdController::class)->only(['destroy'])->middleware('permission:opd.delete');
        Route::get('/opd/list', [OpdController::class, 'getOpdList'])->name('opd.list')->middleware('permission:opd.view');
        Route::get('/opd/search', [OpdController::class, 'search'])->name('opd.search')->middleware('permission:opd.view');
        Route::get('/opd/stats', [OpdController::class, 'getStats'])->name('opd.stats')->middleware('permission:opd.view');

        // User Management
        Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('permission:users.view');
        Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware('permission:users.create');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show')->middleware('permission:users.view');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('permission:users.edit');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('permission:users.delete');
    });

    // Role Management - hanya Super Admin
    Route::middleware(['auth'])->group(function () {
        Route::resource('roles', RoleController::class)->only(['index', 'show'])->middleware('permission:roles.view');
        Route::resource('roles', RoleController::class)->only(['store'])->middleware('permission:roles.create');
        Route::resource('roles', RoleController::class)->only(['update'])->middleware('permission:roles.edit');
        Route::resource('roles', RoleController::class)->only(['destroy'])->middleware('permission:roles.delete');
        Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])
            ->name('roles.permissions')
            ->middleware('permission:roles.view');
        Route::put('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
            ->name('roles.permissions.sync')
            ->middleware('permission:roles.edit');
    });

    /*
    |--------------------------------------------------------------------------
    | General API Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('api')->name('api.')->group(function () {

        // Categories
        Route::get('/categories', [DataSpatialController::class, 'getCategories'])->name('categories')->middleware('permission:data-spatial.view');

        // DBF attribute columns (for bulk attribute editor autocomplete)
        Route::get('/dbf-columns', [DataSpatialController::class, 'getDbfColumns'])->name('dbf-columns')->middleware('permission:data-spatial.view');
        Route::get('/categories-by-opd/{opd}', [KategoriAspirasiController::class, 'getByOpd'])->name('categories-by-opd')->middleware('permission:aspirasi.view');

        // Data Spatial Details
        Route::get('/data-spatial/{uuid}/details', function ($uuid) {
            $data = DataSpatial::with(['kategori', 'opdPengelola'])->where('uuid', $uuid)->first();

            return response()->json([
                'success' => $data ? true : false,
                'data' => $data,
                'message' => $data ? 'Data ditemukan' : 'Data tidak ditemukan',
            ]);
        })->name('data-spatial.details')->middleware('permission:data-spatial.view');
    });

    /*
    |--------------------------------------------------------------------------
    | publications Routes
    |--------------------------------------------------------------------------
    */
    // routes/web.php

    Route::prefix('publications')->name('publications.')->group(function () {
        // List all publications for admin
        Route::get('/', [PublicationController::class, 'adminIndex'])->name('index')->middleware('permission:publications.view');

        // Create new publication
        Route::get('/create', [PublicationController::class, 'create'])->name('create')->middleware('permission:publications.create');
        Route::post('/', [PublicationController::class, 'store'])->name('store')->middleware('permission:publications.create');

        // Edit publication
        Route::get('/{publication}/edit', [PublicationController::class, 'edit'])->name('edit')->middleware('permission:publications.edit');
        Route::put('/{publication}', [PublicationController::class, 'update'])->name('update')->middleware('permission:publications.edit');

        // Delete publication
        Route::delete('/{publication}', [PublicationController::class, 'destroy'])->name('destroy')->middleware('permission:publications.delete');

        // Download and Preview - FIXED
        Route::get('/{publication}/download', [PublicationController::class, 'download'])->name('download')->middleware('permission:publications.view');
        Route::get('/{publication}/preview', [PublicationController::class, 'preview'])->name('preview')->middleware('permission:publications.view');

        // Toggle publication status
        Route::patch('/{publication}/toggle-status', [PublicationController::class, 'toggleStatus'])->name('toggle-status')->middleware('permission:publications.edit');

        // Bulk actions
        Route::post('/bulk-delete', [PublicationController::class, 'bulkDelete'])->name('bulk-delete')->middleware('permission:publications.delete');
        Route::post('/bulk-toggle-status', [PublicationController::class, 'bulkToggleStatus'])->name('bulk-toggle-status')->middleware('permission:publications.edit');

        Route::prefix('downloads')->name('downloads.')->group(function () {
            Route::get('/', [PublicationDownloadController::class, 'index'])->name('index')->middleware('permission:publications.view');
            Route::get('/publication/{publication}', [PublicationDownloadController::class, 'show'])->name('show')->middleware('permission:publications.view');
            Route::get('/analytics', [PublicationDownloadController::class, 'analytics'])->name('analytics')->middleware('permission:publications.view');
            Route::get('/export', [PublicationDownloadController::class, 'export'])->name('export')->middleware('permission:publications.view');
            Route::delete('/{download}', [PublicationDownloadController::class, 'destroy'])->name('destroy')->middleware('permission:publications.delete');
            Route::post('/bulk-destroy', [PublicationDownloadController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware('permission:publications.delete');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Log Management (super-admin only)
    |--------------------------------------------------------------------------
    */

    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [LogController::class, 'index'])->name('index')->middleware('permission:logs.view');
        Route::get('/download', [LogController::class, 'download'])->name('download')->middleware('permission:logs.manage');
        Route::post('/clear', [LogController::class, 'clear'])->name('clear')->middleware('permission:logs.manage');
        Route::delete('/destroy', [LogController::class, 'destroy'])->name('destroy')->middleware('permission:logs.manage');
        Route::delete('/prune-old', [LogController::class, 'pruneOld'])->name('prune-old')->middleware('permission:logs.manage');
    });

});
// Route::get('/coming-soon', function () {
//     return view('coming_soon');
// })->name('coming_soon_public');
