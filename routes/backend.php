<?php
use App\Http\Controllers\KategoriLayerController;
use App\Http\Controllers\KategoriPSDController;
use App\Http\Controllers\KategoriPSNController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\MalukuUtaraController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectFeedbackController;
use App\Http\Controllers\ProyekStrategisDaerahController;
use App\Http\Controllers\ProyekStrategisNasionalController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Lokasi routes with auth
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/lokasi', [LokasiController::class, 'index'])->name('lokasi.index');
    Route::get('/dashboard/lokasi/create', [LokasiController::class, 'create'])->name('lokasi.create');
    Route::post('/dashboard/lokasi', [LokasiController::class, 'store'])->name('lokasi.store');
    Route::get('/dashboard/lokasi/{id}/edit', [LokasiController::class, 'edit'])->name('lokasi.edit');
    Route::put('/dashboard/lokasi/{id}', [LokasiController::class, 'update'])->name('lokasi.update');
    Route::delete('/dashboard/lokasi/{id}', [LokasiController::class, 'destroy'])->name('lokasi.destroy');

    Route::get('/dashboard/peta', [LokasiController::class, 'peta'])->name('lokasi.peta');
});

Route::group(['prefix' => 'dashboard', 'middleware' => ['auth', 'verified']], function () {
    Route::resource('kategori-layers', KategoriLayerController::class);

    Route::get('/coming-soon', function () {
        return view('backend.cooming_soon');
    })->name('cooming_soon');
});

// Project Feedback Routes with auth
Route::prefix('project-feedbacks')->name('project-feedbacks.')->middleware(['auth', 'verified'])->group(function () {
    // Main index page
    Route::get('/', [ProjectFeedbackController::class, 'index'])->name('index');
    
    // Store new feedback
    Route::post('/', [ProjectFeedbackController::class, 'store'])->name('store');
    
    // Show specific feedback (for modal detail)
    Route::get('/{id}', [ProjectFeedbackController::class, 'show'])->name('show');
    
   // Update feedback response (use PUT method properly)
    Route::put('/{id}/respond', [ProjectFeedbackController::class, 'respond'])->name('respond');
    
    // Delete feedback
    Route::delete('/{id}', [ProjectFeedbackController::class, 'destroy'])->name('destroy');
});

// Statistics endpoint with auth
Route::get('/project-feedbacks-statistics', [ProjectFeedbackController::class, 'statistics'])
    ->middleware(['auth', 'verified'])
    ->name('project-feedbacks.statistics');

// Maluku Utara Reference Data Routes with auth
Route::prefix('maluku-utara')->name('maluku-utara.')->middleware(['auth', 'verified'])->group(function () {
    // Get reference data (kabupaten list)
    Route::get('/reference', [MalukuUtaraController::class, 'reference'])->name('reference');
    
    // Get kecamatan by kabupaten
    Route::get('/kecamatan/{kabupaten}', [MalukuUtaraController::class, 'kecamatan'])->name('kecamatan');
});


// ProyekStrategisDaerahController
Route::prefix('dashboard')->middleware('auth')->group(function () {
    
    // Route untuk Proyek Strategis Daerah dengan prefix psd
    Route::prefix('psd')->name('psd.')->group(function () {
        
        // Route utama untuk semua data (tanpa filter tahun)
        Route::get('/', [ProyekStrategisDaerahController::class, 'index'])
            ->name('index');
        
        Route::get('/create', [ProyekStrategisDaerahController::class, 'create'])
            ->name('create');
        
        Route::post('/', [ProyekStrategisDaerahController::class, 'store'])
            ->name('store');
        
        Route::get('/{id}/edit', [ProyekStrategisDaerahController::class, 'edit'])
            ->where('id', '[0-9]+')
            ->name('edit');
        
        Route::put('/{id}', [ProyekStrategisDaerahController::class, 'update'])
            ->where('id', '[0-9]+')
            ->name('update');
        
        Route::delete('/{id}', [ProyekStrategisDaerahController::class, 'destroy'])
            ->where('id', '[0-9]+')
            ->name('destroy');
        
        // Route untuk debugging shapefile
        Route::post('/debug-shapefile', [ProyekStrategisDaerahController::class, 'debugShapefile'])
            ->name('debug-shapefile');
        
        // Route untuk data per tahun (dinamis berdasarkan data yang ada)
        Route::prefix('tahun')->name('tahun.')->group(function () {
            
            // Route untuk menampilkan daftar tahun yang tersedia
            Route::get('/', [ProyekStrategisDaerahController::class, 'getAvailableYears'])
                ->name('index');
            
            // Route dinamis untuk tahun yang ada di database
            Route::get('/{year}', [ProyekStrategisDaerahController::class, 'indexByYear'])
                ->where('year', '[0-9]{4}')
                ->name('show');
            
            Route::get('/{year}/create', [ProyekStrategisDaerahController::class, 'createByYear'])
                ->where('year', '[0-9]{4}')
                ->name('create');
            
            Route::post('/{year}', [ProyekStrategisDaerahController::class, 'storeByYear'])
                ->where('year', '[0-9]{4}')
                ->name('store');
            
            Route::get('/{year}/edit/{id}', [ProyekStrategisDaerahController::class, 'editByYear'])
                ->where(['year' => '[0-9]{4}', 'id' => '[0-9]+'])
                ->name('edit');
            
            Route::put('/{year}/update/{id}', [ProyekStrategisDaerahController::class, 'updateByYear'])
                ->where(['year' => '[0-9]{4}', 'id' => '[0-9]+'])
                ->name('update');
            
            Route::delete('/{year}/delete/{id}', [ProyekStrategisDaerahController::class, 'destroyByYear'])
                ->where(['year' => '[0-9]{4}', 'id' => '[0-9]+'])
                ->name('destroy');
        });
        
        // Route untuk kategori proyek daerah
        Route::prefix('kategori')->name('kategori.')->group(function () {
            
            Route::get('/', [KategoriPSDController::class, 'indexByCategory'])
                ->name('index');
            
            // Route untuk create proyek baru (tanpa kategori spesifik)
            Route::get('/create', [ProyekStrategisDaerahController::class, 'create'])
                ->name('create');
            
            Route::get('/{kategori}', [KategoriPSDController::class, 'showByCategory'])
                ->where('kategori', '[0-9]+')
                ->name('show');
                
            Route::get('/{kategori}/create', [KategoriPSDController::class, 'createByCategory'])
                ->where('kategori', '[0-9]+')
                ->name('create.specific');
            
            // Route kategori per tahun
            Route::get('/{kategori}/tahun/{year}', [KategoriPSDController::class, 'showByCategoryAndYear'])
                ->where(['kategori' => '[0-9]+', 'year' => '[0-9]{4}'])
                ->name('show.year');
        });
        
        // Route untuk peta
        Route::get('/peta', [ProyekStrategisDaerahController::class, 'peta'])
            ->name('peta');
        
        // Route untuk peta berdasarkan tahun
        Route::get('/peta/{year}', [ProyekStrategisDaerahController::class, 'petaByYear'])
            ->where('year', '[0-9]{4}')
            ->name('peta.year');
        
        // API Routes untuk data GeoJSON dan informasi
        Route::prefix('api')->name('api.')->group(function () {
            
            // Route untuk mendapatkan tahun yang tersedia
            Route::get('/years', [ProyekStrategisDaerahController::class, 'getAvailableYearsApi'])
                ->name('years');
            
            // Route untuk mendapatkan data GeoJSON
            Route::get('/geojson', [ProyekStrategisDaerahController::class, 'geojson'])
                ->name('geojson');
            
            // Route untuk mendapatkan data GeoJSON berdasarkan tahun
            Route::get('/geojson/{year}', [ProyekStrategisDaerahController::class, 'geojsonByYear'])
                ->where('year', '[0-9]{4}')
                ->name('geojson.year');
            
            // Route untuk mendapatkan kolom DBF
            Route::get('/dbf-columns', [ProyekStrategisDaerahController::class, 'getDbfColumns'])
                ->name('dbf-columns');
            
            // Route untuk mendapatkan kolom DBF berdasarkan tahun
            Route::get('/dbf-columns/{year}', [ProyekStrategisDaerahController::class, 'getDbfColumnsByYear'])
                ->where('year', '[0-9]{4}')
                ->name('dbf-columns.year');
            
            // Route untuk mendapatkan nilai kolom DBF tertentu
            Route::get('/dbf-columns/{column}/values', [ProyekStrategisDaerahController::class, 'getDbfColumnValues'])
                ->name('dbf-column-values');
            
            // Route untuk mendapatkan nilai kolom DBF berdasarkan tahun
            Route::get('/dbf-columns/{column}/values/{year}', [ProyekStrategisDaerahController::class, 'getDbfColumnValuesByYear'])
                ->where('year', '[0-9]{4}')
                ->name('dbf-column-values.year');
            
            // Route untuk mendapatkan daftar kategori
            Route::get('/categories', [ProyekStrategisDaerahController::class, 'getCategories'])
                ->name('categories');
            
            // Route untuk mendapatkan kategori berdasarkan tahun
            Route::get('/categories/{year}', [ProyekStrategisDaerahController::class, 'getCategoriesByYear'])
                ->where('year', '[0-9]{4}')
                ->name('categories.year');
            
            // Route untuk mendapatkan statistik data
            Route::get('/statistics', [ProyekStrategisDaerahController::class, 'getStatistics'])
                ->name('statistics');
            
            // Route untuk mendapatkan statistik berdasarkan tahun
            Route::get('/statistics/{year}', [ProyekStrategisDaerahController::class, 'getStatisticsByYear'])
                ->where('year', '[0-9]{4}')
                ->name('statistics.year');
            
            // Route untuk mendapatkan data berdasarkan kategori
            Route::get('/category/{kategori}', [ProyekStrategisDaerahController::class, 'getByCategory'])
                ->where('kategori', '[0-9]+')
                ->name('category');
            
            // Route untuk mendapatkan data berdasarkan kategori dan tahun
            Route::get('/category/{kategori}/{year}', [ProyekStrategisDaerahController::class, 'getByCategoryAndYear'])
                ->where(['kategori' => '[0-9]+', 'year' => '[0-9]{4}'])
                ->name('category.year');
        });
    });

    // Route terpisah untuk manajemen Kategori PSD (CRUD)
    Route::prefix('kategori-psd')->name('kategori-psd.')->group(function () {
        
        // Route index
        Route::get('/', [KategoriPSDController::class, 'index'])
            ->name('index');
        
        // Route create
        Route::get('/create', [KategoriPSDController::class, 'create'])
            ->name('create');
        
        Route::post('/', [KategoriPSDController::class, 'store'])
            ->name('store');
        
        // Route dengan ID constraint untuk menghindari konflik
        Route::get('/{kategoriPsd}/edit', [KategoriPSDController::class, 'edit'])
            ->where('kategoriPsd', '[0-9]+')
            ->name('edit');
        
        Route::put('/{kategoriPsd}', [KategoriPSDController::class, 'update'])
            ->where('kategoriPsd', '[0-9]+')
            ->name('update');
        
        Route::delete('/{kategoriPsd}', [KategoriPSDController::class, 'destroy'])
            ->where('kategoriPsd', '[0-9]+')
            ->name('destroy');
        
        Route::get('/{kategoriPsd}', [KategoriPSDController::class, 'show'])
            ->where('kategoriPsd', '[0-9]+')
            ->name('show');
        
        // API Routes untuk kategori
        Route::prefix('api')->name('api.')->group(function () {
            
            Route::get('/categories', [KategoriPSDController::class, 'getCategoriesApi'])
                ->name('categories');
            
            Route::get('/statistics/{kategoriId}', [KategoriPSDController::class, 'getCategoryStatistics'])
                ->where('kategoriId', '[0-9]+')
                ->name('statistics');
        });
    });
});

// Route fallback untuk menangani halaman yang tidak ditemukan (opsional)
// Catatan: Fallback route sebaiknya ditempatkan di akhir file route
/*
Route::fallback(function () {
    return redirect()->route('psd.index')->with('error', 'Halaman tidak ditemukan.');
});
*/
// end ProyekStrategisDaerahController


// // ProyekStrategisNasionalController
Route::prefix('dashboard')->middleware('auth')->group(function () {
    
    // Route untuk Proyek Strategis Daerah dengan prefix psd
    Route::prefix('psn')->name('psn.')->group(function () {
        
        // Route utama untuk semua data (tanpa filter tahun)
        Route::get('/', [ProyekStrategisNasionalController::class, 'index'])
            ->name('index');
        
        Route::get('/create', [ProyekStrategisNasionalController::class, 'create'])
            ->name('create');
        
        Route::post('/', [ProyekStrategisNasionalController::class, 'store'])
            ->name('store');
        
        Route::get('/{id}/edit', [ProyekStrategisNasionalController::class, 'edit'])
            ->where('id', '[0-9]+')
            ->name('edit');
        
        Route::put('/{id}', [ProyekStrategisNasionalController::class, 'update'])
            ->where('id', '[0-9]+')
            ->name('update');
        
        Route::delete('/{id}', [ProyekStrategisNasionalController::class, 'destroy'])
            ->where('id', '[0-9]+')
            ->name('destroy');
        
        // Route untuk debugging shapefile
        Route::post('/debug-shapefile', [ProyekStrategisNasionalController::class, 'debugShapefile'])
            ->name('debug-shapefile');
        
        // Route untuk data per tahun (dinamis berdasarkan data yang ada)
        Route::prefix('tahun')->name('tahun.')->group(function () {
            
            // Route untuk menampilkan daftar tahun yang tersedia
            Route::get('/', [ProyekStrategisNasionalController::class, 'getAvailableYears'])
                ->name('index');
            
            // Route dinamis untuk tahun yang ada di database
            Route::get('/{year}', [ProyekStrategisNasionalController::class, 'indexByYear'])
                ->where('year', '[0-9]{4}')
                ->name('show');
            
            Route::get('/{year}/create', [ProyekStrategisNasionalController::class, 'createByYear'])
                ->where('year', '[0-9]{4}')
                ->name('create');
            
            Route::post('/{year}', [ProyekStrategisNasionalController::class, 'storeByYear'])
                ->where('year', '[0-9]{4}')
                ->name('store');
            
            Route::get('/{year}/edit/{id}', [ProyekStrategisNasionalController::class, 'editByYear'])
                ->where(['year' => '[0-9]{4}', 'id' => '[0-9]+'])
                ->name('edit');
            
            Route::put('/{year}/update/{id}', [ProyekStrategisNasionalController::class, 'updateByYear'])
                ->where(['year' => '[0-9]{4}', 'id' => '[0-9]+'])
                ->name('update');
            
            Route::delete('/{year}/delete/{id}', [ProyekStrategisNasionalController::class, 'destroyByYear'])
                ->where(['year' => '[0-9]{4}', 'id' => '[0-9]+'])
                ->name('destroy');
        });
        
        // Route untuk kategori proyek daerah
        Route::prefix('kategori')->name('kategori.')->group(function () {
            
            Route::get('/', [KategoriPSNController::class, 'indexByCategory'])
                ->name('index');
            
            // Route untuk create proyek baru (tanpa kategori spesifik)
            Route::get('/create', [ProyekStrategisNasionalController::class, 'create'])
                ->name('create');
            
            Route::get('/{kategori}', [KategoriPSNController::class, 'showByCategory'])
                ->where('kategori', '[0-9]+')
                ->name('show');
                
            Route::get('/{kategori}/create', [KategoriPSNController::class, 'createByCategory'])
                ->where('kategori', '[0-9]+')
                ->name('create.specific');
            
            // Route kategori per tahun
            Route::get('/{kategori}/tahun/{year}', [KategoriPSNController::class, 'showByCategoryAndYear'])
                ->where(['kategori' => '[0-9]+', 'year' => '[0-9]{4}'])
                ->name('show.year');
        });
        
        // Route untuk peta
        Route::get('/peta', [ProyekStrategisNasionalController::class, 'peta'])
            ->name('peta');
        
        // Route untuk peta berdasarkan tahun
        Route::get('/peta/{year}', [ProyekStrategisNasionalController::class, 'petaByYear'])
            ->where('year', '[0-9]{4}')
            ->name('peta.year');
        
        // API Routes untuk data GeoJSON dan informasi
        Route::prefix('api')->name('api.')->group(function () {
            
            // Route untuk mendapatkan tahun yang tersedia
            Route::get('/years', [ProyekStrategisNasionalController::class, 'getAvailableYearsApi'])
                ->name('years');
            
            // Route untuk mendapatkan data GeoJSON
            Route::get('/geojson', [ProyekStrategisNasionalController::class, 'geojson'])
                ->name('geojson');
            
            // Route untuk mendapatkan data GeoJSON berdasarkan tahun
            Route::get('/geojson/{year}', [ProyekStrategisNasionalController::class, 'geojsonByYear'])
                ->where('year', '[0-9]{4}')
                ->name('geojson.year');
            
            // Route untuk mendapatkan kolom DBF
            Route::get('/dbf-columns', [ProyekStrategisNasionalController::class, 'getDbfColumns'])
                ->name('dbf-columns');
            
            // Route untuk mendapatkan kolom DBF berdasarkan tahun
            Route::get('/dbf-columns/{year}', [ProyekStrategisNasionalController::class, 'getDbfColumnsByYear'])
                ->where('year', '[0-9]{4}')
                ->name('dbf-columns.year');
            
            // Route untuk mendapatkan nilai kolom DBF tertentu
            Route::get('/dbf-columns/{column}/values', [ProyekStrategisNasionalController::class, 'getDbfColumnValues'])
                ->name('dbf-column-values');
            
            // Route untuk mendapatkan nilai kolom DBF berdasarkan tahun
            Route::get('/dbf-columns/{column}/values/{year}', [ProyekStrategisNasionalController::class, 'getDbfColumnValuesByYear'])
                ->where('year', '[0-9]{4}')
                ->name('dbf-column-values.year');
            
            // Route untuk mendapatkan daftar kategori
            Route::get('/categories', [ProyekStrategisNasionalController::class, 'getCategories'])
                ->name('categories');
            
            // Route untuk mendapatkan kategori berdasarkan tahun
            Route::get('/categories/{year}', [ProyekStrategisNasionalController::class, 'getCategoriesByYear'])
                ->where('year', '[0-9]{4}')
                ->name('categories.year');
            
            // Route untuk mendapatkan statistik data
            Route::get('/statistics', [ProyekStrategisNasionalController::class, 'getStatistics'])
                ->name('statistics');
            
            // Route untuk mendapatkan statistik berdasarkan tahun
            Route::get('/statistics/{year}', [ProyekStrategisNasionalController::class, 'getStatisticsByYear'])
                ->where('year', '[0-9]{4}')
                ->name('statistics.year');
            
            // Route untuk mendapatkan data berdasarkan kategori
            Route::get('/category/{kategori}', [ProyekStrategisNasionalController::class, 'getByCategory'])
                ->where('kategori', '[0-9]+')
                ->name('category');
            
            // Route untuk mendapatkan data berdasarkan kategori dan tahun
            Route::get('/category/{kategori}/{year}', [ProyekStrategisNasionalController::class, 'getByCategoryAndYear'])
                ->where(['kategori' => '[0-9]+', 'year' => '[0-9]{4}'])
                ->name('category.year');
        });
    });

    // // Route terpisah untuk manajemen Kategori PSN (CRUD)
    Route::prefix('kategori-psn')->name('kategori-psn.')->group(function () {
        
        // Route index
        Route::get('/', [KategoriPSNController::class, 'index'])
            ->name('index');
        
        // Route create
        Route::get('/create', [KategoriPSNController::class, 'create'])
            ->name('create');
        
        Route::post('/', [KategoriPSNController::class, 'store'])
            ->name('store');
        
        // Route dengan ID constraint untuk menghindari konflik
        Route::get('/{kategoriPsn}/edit', [KategoriPSNController::class, 'edit'])
            ->where('kategoriPsn', '[0-9]+')
            ->name('edit');
        
        Route::put('/{kategoriPsn}', [KategoriPSNController::class, 'update'])
            ->where('kategoriPsn', '[0-9]+')
            ->name('update');
        
        Route::delete('/{kategoriPsn}', [KategoriPSNController::class, 'destroy'])
            ->where('kategoriPsn', '[0-9]+')
            ->name('destroy');
        
        Route::get('/{kategoriPsn}', [KategoriPSNController::class, 'show'])
            ->where('kategoriPsn', '[0-9]+')
            ->name('show');
        
        // API Routes untuk kategori
        Route::prefix('api')->name('api.')->group(function () {
            
            Route::get('/categories', [KategoriPSNController::class, 'getCategoriesApi'])
                ->name('categories');
            
            Route::get('/statistics/{kategoriId}', [KategoriPSNController::class, 'getCategoryStatistics'])
                ->where('kategoriId', '[0-9]+')
                ->name('statistics');
        });
    });
});

// Route fallback untuk menangani halaman yang tidak ditemukan (opsional)
// Catatan: Fallback route sebaiknya ditempatkan di akhir file route
/*
Route::fallback(function () {
    return redirect()->route('psd.index')->with('error', 'Halaman tidak ditemukan.');
});
*/
// Route perbaikan untuk Kategori PSN
// Route::prefix('dashboard')->middleware('auth')->group(function () {
    
//     // Route untuk Proyek Strategis Nasional dengan prefix psn
//     Route::prefix('psn')->name('psn.')->group(function () {
        
//         // Route utama untuk semua data (tanpa filter tahun)
//         Route::get('/', [ProyekStrategisNasionalController::class, 'index'])
//             ->name('index');
        
//         Route::get('/create', [ProyekStrategisNasionalController::class, 'create'])
//             ->name('create');
        
//         Route::post('/', [ProyekStrategisNasionalController::class, 'store'])
//             ->name('store');
        
//         Route::get('/{id}/edit', [ProyekStrategisNasionalController::class, 'edit'])
//             ->where('id', '[0-9]+')
//             ->name('edit');
        
//         Route::put('/{id}', [ProyekStrategisNasionalController::class, 'update'])
//             ->where('id', '[0-9]+')
//             ->name('update');
        
//         Route::delete('/{id}', [ProyekStrategisNasionalController::class, 'destroy'])
//             ->where('id', '[0-9]+')
//             ->name('destroy');
        
//         // Route untuk debugging shapefile
//         Route::post('/debug-shapefile', [ProyekStrategisNasionalController::class, 'debugShapefile'])
//             ->name('debug-shapefile');
        
//         // Route untuk data per tahun (dinamis berdasarkan data yang ada)
//         Route::prefix('tahun')->name('tahun.')->group(function () {
            
//             // Route untuk menampilkan daftar tahun yang tersedia
//             Route::get('/', [ProyekStrategisNasionalController::class, 'getAvailableYears'])
//                 ->name('index');
            
//             // Route dinamis untuk tahun yang ada di database
//             Route::get('/{year}', [ProyekStrategisNasionalController::class, 'indexByYear'])
//                 ->where('year', '[0-9]{4}')
//                 ->name('show');
            
//             Route::get('/{year}/create', [ProyekStrategisNasionalController::class, 'createByYear'])
//                 ->where('year', '[0-9]{4}')
//                 ->name('create');
            
//             Route::post('/{year}', [ProyekStrategisNasionalController::class, 'storeByYear'])
//                 ->where('year', '[0-9]{4}')
//                 ->name('store');
            
//             Route::get('/{year}/edit/{id}', [ProyekStrategisNasionalController::class, 'editByYear'])
//                 ->where(['year' => '[0-9]{4}', 'id' => '[0-9]+'])
//                 ->name('edit');
            
//             Route::put('/{year}/update/{id}', [ProyekStrategisNasionalController::class, 'updateByYear'])
//                 ->where(['year' => '[0-9]{4}', 'id' => '[0-9]+'])
//                 ->name('update');
            
//             Route::delete('/{year}/delete/{id}', [ProyekStrategisNasionalController::class, 'destroyByYear'])
//                 ->where(['year' => '[0-9]{4}', 'id' => '[0-9]+'])
//                 ->name('destroy');
//         });
        
//         // Route untuk kategori proyek nasional
//         Route::prefix('kategori')->name('kategori.')->group(function () {
            
//             Route::get('/', [KategoriPSNController::class, 'indexByCategory'])
//                 ->name('index');
            
//             // Route untuk create proyek baru (tanpa kategori spesifik)
//             Route::get('/create', [ProyekStrategisNasionalController::class, 'create'])
//                 ->name('create');
            
//             Route::get('/{kategori}', [KategoriPSNController::class, 'showByCategory'])
//                 ->where('kategori', '[0-9]+')
//                 ->name('show');
                
//             Route::get('/{kategori}/create', [KategoriPSNController::class, 'createByCategory'])
//                 ->where('kategori', '[0-9]+')
//                 ->name('create.specific');
            
//             // Route kategori per tahun
//             Route::get('/{kategori}/tahun/{year}', [KategoriPSNController::class, 'showByCategoryAndYear'])
//                 ->where(['kategori' => '[0-9]+', 'year' => '[0-9]{4}'])
//                 ->name('show.year');
//         });
        
//         // Route untuk peta
//         Route::get('/peta', [ProyekStrategisNasionalController::class, 'peta'])
//             ->name('peta');
        
//         // Route untuk peta berdasarkan tahun
//         Route::get('/peta/{year}', [ProyekStrategisNasionalController::class, 'petaByYear'])
//             ->where('year', '[0-9]{4}')
//             ->name('peta.year');
        
//         // API Routes untuk data GeoJSON dan informasi
//         Route::prefix('api')->name('api.')->group(function () {
            
//             // Route untuk mendapatkan tahun yang tersedia
//             Route::get('/years', [ProyekStrategisNasionalController::class, 'getAvailableYearsApi'])
//                 ->name('years');
            
//             // Route untuk mendapatkan data GeoJSON
//             Route::get('/geojson', [ProyekStrategisNasionalController::class, 'geojson'])
//                 ->name('geojson');
            
//             // Route untuk mendapatkan data GeoJSON berdasarkan tahun
//             Route::get('/geojson/{year}', [ProyekStrategisNasionalController::class, 'geojsonByYear'])
//                 ->where('year', '[0-9]{4}')
//                 ->name('geojson.year');
            
//             // Route untuk mendapatkan kolom DBF
//             Route::get('/dbf-columns', [ProyekStrategisNasionalController::class, 'getDbfColumns'])
//                 ->name('dbf-columns');
            
//             // Route untuk mendapatkan kolom DBF berdasarkan tahun
//             Route::get('/dbf-columns/{year}', [ProyekStrategisNasionalController::class, 'getDbfColumnsByYear'])
//                 ->where('year', '[0-9]{4}')
//                 ->name('dbf-columns.year');
            
//             // Route untuk mendapatkan nilai kolom DBF tertentu
//             Route::get('/dbf-columns/{column}/values', [ProyekStrategisNasionalController::class, 'getDbfColumnValues'])
//                 ->name('dbf-column-values');
            
//             // Route untuk mendapatkan nilai kolom DBF berdasarkan tahun
//             Route::get('/dbf-columns/{column}/values/{year}', [ProyekStrategisNasionalController::class, 'getDbfColumnValuesByYear'])
//                 ->where('year', '[0-9]{4}')
//                 ->name('dbf-column-values.year');
            
//             // Route untuk mendapatkan daftar kategori
//             Route::get('/categories', [ProyekStrategisNasionalController::class, 'getCategories'])
//                 ->name('categories');
            
//             // Route untuk mendapatkan kategori berdasarkan tahun
//             Route::get('/categories/{year}', [ProyekStrategisNasionalController::class, 'getCategoriesByYear'])
//                 ->where('year', '[0-9]{4}')
//                 ->name('categories.year');
            
//             // Route untuk mendapatkan statistik data
//             Route::get('/statistics', [ProyekStrategisNasionalController::class, 'getStatistics'])
//                 ->name('statistics');
            
//             // Route untuk mendapatkan statistik berdasarkan tahun
//             Route::get('/statistics/{year}', [ProyekStrategisNasionalController::class, 'getStatisticsByYear'])
//                 ->where('year', '[0-9]{4}')
//                 ->name('statistics.year');
            
//             // Route untuk mendapatkan data berdasarkan kategori
//             Route::get('/category/{kategori}', [ProyekStrategisNasionalController::class, 'getByCategory'])
//                 ->where('kategori', '[0-9]+')
//                 ->name('category');
            
//             // Route untuk mendapatkan data berdasarkan kategori dan tahun
//             Route::get('/category/{kategori}/{year}', [ProyekStrategisNasionalController::class, 'getByCategoryAndYear'])
//                 ->where(['kategori' => '[0-9]+', 'year' => '[0-9]{4}'])
//                 ->name('category.year');
//         });
//     });

//     // Route terpisah untuk manajemen Kategori PSN (CRUD)
//     Route::prefix('kategori-psn')->name('kategori-psn.')->group(function () {
        
//         // Route index
//         Route::get('/', [KategoriPSNController::class, 'index'])
//             ->name('index');
        
//         // Route create
//         Route::get('/create', [KategoriPSNController::class, 'create'])
//             ->name('create');
        
//         Route::post('/', [KategoriPSNController::class, 'store'])
//             ->name('store');
        
//         // PERBAIKAN: Tambahkan route show sebelum edit/update/destroy untuk menghindari konflik
//         Route::get('/{kategoriPsn}/show', [KategoriPSNController::class, 'show'])
//             ->where('kategoriPsn', '[0-9]+')
//             ->name('show');
        
//         // Route edit (perbaikan: tambahkan /edit di URL)
//         Route::get('/{kategoriPsn}/edit', [KategoriPSNController::class, 'edit'])
//             ->where('kategoriPsn', '[0-9]+')
//             ->name('edit');
        
//         Route::put('/{kategoriPsn}', [KategoriPSNController::class, 'update'])
//             ->where('kategoriPsn', '[0-9]+')
//             ->name('update');
        
//         Route::delete('/{kategoriPsn}', [KategoriPSNController::class, 'destroy'])
//             ->where('kategoriPsn', '[0-9]+')
//             ->name('destroy');
        
//         // API Routes untuk kategori
//         Route::prefix('api')->name('api.')->group(function () {
            
//             Route::get('/categories', [KategoriPSNController::class, 'getCategoriesApi'])
//                 ->name('categories');
            
//             Route::get('/statistics/{kategoriId}', [KategoriPSNController::class, 'getCategoryStatistics'])
//                 ->where('kategoriId', '[0-9]+')
//                 ->name('statistics');
//         });
//     });
// });
// end ProyekStrategisnasinonal
// require __DIR__.'/auth.php';