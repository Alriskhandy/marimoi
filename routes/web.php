<?php

use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\LokasiController;
use Illuminate\Support\Facades\Route;

// HALAMAN //
Route::get('/', [FrontendController::class, 'index'])->name('beranda');
Route::get('/proyek-strategis-daerah', [FrontendController::class, 'psd'])->name('tampil.psd');
Route::get('/proyek-strategis-nasional', [FrontendController::class, 'psn'])->name('tampil.psn');
Route::get('/prioritas-daerah', [FrontendController::class, 'prioritas'])->name('tampil.prioritas');
Route::get('/peta-tematik', [FrontendController::class, 'tematik'])->name('tampil.tematik');
Route::get('/usulan-musrenbang', [FrontendController::class, 'musrenbang'])->name('tampil.musrenbang');
Route::get('/pokir-dprd', [FrontendController::class, 'pokir'])->name('tampil.pokir');
Route::get('/aspirasi-masyarakat', [FrontendController::class, 'aspirasi'])->name('tampil.aspirasi');

// HALAMAN DETAIL //
Route::get('/proyek-strategis-daerah/{id}', [FrontendController::class, 'detailPeta'])->name('detail.psd');
Route::get('/proyek-strategis-nasional/{id}', [FrontendController::class, 'detailPeta'])->name('detail.psn');
Route::get('/peta-tematik/{id}', [FrontendController::class, 'detailPeta'])->name('detail.tematik');
Route::get('/rpjmd/{id}', [FrontendController::class, 'detailPeta'])->name('detail.rpjmd');
Route::get('/pokir-dprd/{id}', [FrontendController::class, 'detailPeta'])->name('detail.pokir');
Route::get('/usulan-musrenbang/{id}', [FrontendController::class, 'detailPeta'])->name('detail.musrenbang');

// FEEDBACK //
Route::post('/feedback-send', [FrontendController::class, 'store'])->name('feedback.store');

// API GEOJSON //
Route::get('/geojson', [FrontendController::class, 'getGeojsonByDataType']);



// TAHAP PENGEMBANGAN //
Route::get('/peta-gis', [FrontendController::class, 'showMap'])->name('tampil.peta');

// Routes untuk project-specific feedback
Route::post('pokir/feedback/store/{projectId?}', [FrontendController::class, 'store'])->name('pokir.feedback.store');
Route::post('usulan/feedback/store/{projectId?}', [FrontendController::class, 'store'])->name('usulan.feedback.store');
Route::post('nasional/feedback/store/{projectId?}', [FrontendController::class, 'store'])->name('nasional.feedback.store');
Route::post('daerah/feedback/store/{projectId?}', [FrontendController::class, 'store'])->name('daerah.feedback.store');
Route::post('lokasi/feedback/store/{projectId?}', [FrontendController::class, 'store'])->name('lokasi.feedback.store');

require __DIR__.'/auth.php';
require __DIR__.'/backend.php';
