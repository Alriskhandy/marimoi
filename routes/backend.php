<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DataSpatialController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\ProjectFeedbackController;
use App\Http\Controllers\MalukuUtaraController;
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

    // === PETA RPJMD (LOKASI) ROUTES ===
    Route::prefix('lokasi')->name('lokasi.')->group(function () {
        Route::get('/', [DataSpatialController::class, 'indexLokasi'])->name('index');
        Route::get('/create', function() {
            return redirect()->route('data-spatial.create') . '?type=lokasi';
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
            ->defaults('data_type', 'lokasi')->name('geojson');
        Route::get('/statistics', [DataSpatialController::class, 'getStatistics'])
            ->defaults('data_type', 'lokasi')->name('statistics');
        Route::get('/categories', [DataSpatialController::class, 'getCategories'])
            ->defaults('data_type', 'lokasi')->name('categories');
        Route::get('/dbf-columns', [DataSpatialController::class, 'getDbfColumns'])
            ->defaults('data_type', 'lokasi')->name('dbf.columns');
        Route::get('/dbf-columns/{column}/values', [DataSpatialController::class, 'getDbfColumnValues'])
            ->defaults('data_type', 'lokasi')->name('dbf.values');
        
        // Peta view
        Route::get('/peta', function() {
            return view('backend.pages.maps.lokasi');
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
    Route::prefix('kategori-layers')->name('kategori-layers.')->group(function () {
        Route::get('/', function() {
            return redirect()->route('categories.index') . '?type=layers';
        })->name('index');
        Route::get('/create', function() {
            return redirect()->route('categories.create') . '?type=layers';
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
            return redirect()->route('categories.index') . '?type=musrenbangs';
        })->name('index');
        Route::get('/create', function() {
            return redirect()->route('categories.create') . '?type=musrenbangs';
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
            return redirect()->route('categories.index') . '?type=pokir_dprds';
        })->name('index');
        Route::get('/create', function() {
            return redirect()->route('categories.create') . '?type=pokir_dprds';
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
    // General project feedbacks (all types)
    Route::prefix('project-feedbacks')->name('project-feedbacks.')->group(function() {
        Route::get('/', [ProjectFeedbackController::class, 'index'])->name('index');
        Route::post('/', [ProjectFeedbackController::class, 'store'])->name('store');
        Route::get('/{id}', [ProjectFeedbackController::class, 'show'])->name('show');
        Route::put('/{id}/respond', [ProjectFeedbackController::class, 'respond'])->name('respond');
        Route::delete('/{id}', [ProjectFeedbackController::class, 'destroy'])->name('destroy');
        Route::get('/statistics', [ProjectFeedbackController::class, 'statistics'])->name('statistics');
    });

    // Scoped project feedbacks (per project type)
    Route::prefix('pokir')->name('pokir.')->group(function() {
        Route::prefix('project-feedbacks')->name('feedbacks.')->group(function() {
            Route::get('/', [ProjectFeedbackController::class, 'index'])->name('index');
            Route::post('/', [ProjectFeedbackController::class, 'store'])->name('store');
            Route::get('/{id}', [ProjectFeedbackController::class, 'show'])->name('show');
            Route::put('/{id}/respond', [ProjectFeedbackController::class, 'respond'])->name('respond');
            Route::delete('/{id}', [ProjectFeedbackController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('usulan')->name('usulan.')->group(function() {
        Route::prefix('project-feedbacks')->name('feedbacks.')->group(function() {
            Route::get('/', [ProjectFeedbackController::class, 'index'])->name('index');
            Route::post('/', [ProjectFeedbackController::class, 'store'])->name('store');
            Route::get('/{id}', [ProjectFeedbackController::class, 'show'])->name('show');
            Route::put('/{id}/respond', [ProjectFeedbackController::class, 'respond'])->name('respond');
            Route::delete('/{id}', [ProjectFeedbackController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('nasional')->name('nasional.')->group(function() {
        Route::prefix('project-feedbacks')->name('feedbacks.')->group(function() {
            Route::get('/', [ProjectFeedbackController::class, 'index'])->name('index');
            Route::post('/', [ProjectFeedbackController::class, 'store'])->name('store');
            Route::get('/{id}', [ProjectFeedbackController::class, 'show'])->name('show');
            Route::put('/{id}/respond', [ProjectFeedbackController::class, 'respond'])->name('respond');
            Route::delete('/{id}', [ProjectFeedbackController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('daerah')->name('daerah.')->group(function() {
        Route::prefix('project-feedbacks')->name('feedbacks.')->group(function() {
            Route::get('/', [ProjectFeedbackController::class, 'index'])->name('index');
            Route::post('/', [ProjectFeedbackController::class, 'store'])->name('store');
            Route::get('/{id}', [ProjectFeedbackController::class, 'show'])->name('show');
            Route::put('/{id}/respond', [ProjectFeedbackController::class, 'respond'])->name('respond');
            Route::delete('/{id}', [ProjectFeedbackController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('lokasi')->name('lokasi.')->group(function() {
        Route::prefix('project-feedbacks')->name('feedbacks.')->group(function() {
            Route::get('/', [ProjectFeedbackController::class, 'index'])->name('index');
            Route::post('/', [ProjectFeedbackController::class, 'store'])->name('store');
            Route::get('/{id}', [ProjectFeedbackController::class, 'show'])->name('show');
            Route::put('/{id}/respond', [ProjectFeedbackController::class, 'respond'])->name('respond');
            Route::delete('/{id}', [ProjectFeedbackController::class, 'destroy'])->name('destroy');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Maluku Utara Reference Data Routes
|--------------------------------------------------------------------------
*/

Route::prefix('maluku-utara')->name('maluku-utara.')->middleware(['auth'])->group(function () {
    Route::get('/reference', [MalukuUtaraController::class, 'reference'])->name('reference');
    Route::get('/kecamatan/{kabupaten}', [MalukuUtaraController::class, 'kecamatan'])->name('kecamatan');
});

/*
|--------------------------------------------------------------------------
| Backward Compatibility & Legacy Route Redirects
|--------------------------------------------------------------------------
*/

Route::prefix('dashboard')->middleware(['auth'])->group(function () {
    // Old dashboard routes
    Route::get('/lokasis', function() {
        return redirect()->route('lokasi.index');
    });
    
    Route::get('/usulan-musrenbangs', function() {
        return redirect()->route('usulan-musrenbang.index');
    });
    
    Route::get('/pokir-dprds', function() {
        return redirect()->route('pokir-dprd.index');
    });
    
    Route::get('/proyek-strategis-daerahs', function() {
        return redirect()->route('psd.index');
    });
    
    Route::get('/proyek-strategis-nasionals', function() {
        return redirect()->route('psn.index');
    });
    
    // Legacy API routes
    Route::get('/lokasis/geojson', function() {
        return redirect()->route('lokasi.geojson');
    });
    
    Route::get('/proyek-strategis-daerahs/geojson', function() {
        return redirect()->route('psd.geojson');
    });
    
    Route::get('/proyek-strategis-daerahs/tahun/{year}', function($year) {
        return redirect()->route('psd.tahun.show', $year);
    });
    
    Route::get('/proyek-strategis-nasionals/tahun/{year}', function($year) {
        return redirect()->route('psn.tahun.show', $year);
    });
});

// Dashboard legacy routes (keeping /dashboard prefix for compatibility)
Route::prefix('dashboard')->middleware(['auth'])->group(function () {
    Route::get('/lokasi', function() {
        return redirect()->route('lokasi.index');
    });
    
    Route::get('/pokir-dprd', function() {
        return redirect()->route('pokir-dprd.index');
    });
    
    Route::get('/usulan-musrenbang', function() {
        return redirect()->route('usulan-musrenbang.index');
    });
    
    Route::get('/psd', function() {
        return redirect()->route('psd.index');
    });
    
    Route::get('/psn', function() {
        return redirect()->route('psn.index');
    });
    
    Route::get('/peta', function() {
        return redirect()->route('lokasi.peta');
    });
    
    Route::get('/peta-pokir', function() {
        return redirect()->route('pokir-dprd.peta');
    });
    
    Route::get('/peta-usulan-musrenbang', function() {
        return redirect()->route('usulan-musrenbang.peta');
    });
});
