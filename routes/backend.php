<?php
use App\Http\Controllers\KategoriLayerController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\MalukuUtaraController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectFeedbackController;
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

    Route::get('/cooming-soon', function () {
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

require __DIR__.'/auth.php';