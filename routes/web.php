<?php

use App\Http\Controllers\FrontendController;
use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

// HALAMAN //
Route::get('/', [FrontendController::class, 'indexDark'])->name('beranda');
Route::get('/profil-reformer', [FrontendController::class, 'reformer'])->name('tampil.reformer');
Route::get('/proyek-strategis-daerah', [FrontendController::class, 'psd'])->name('tampil.psd');
Route::get('/proyek-strategis-nasional', [FrontendController::class, 'psn'])->name('tampil.psn');
Route::get('/prioritas-daerah', [FrontendController::class, 'prioritas'])->name('tampil.prioritas');
Route::get('/peta-tematik', [FrontendController::class, 'tematik'])->name('tampil.tematik');
Route::get('/usulan-musrenbang', [FrontendController::class, 'musrenbang'])->name('tampil.musrenbang');
Route::get('/pokir-dprd', [FrontendController::class, 'pokir'])->name('tampil.pokir');
Route::get('/dokumen-publikasi', [FrontendController::class, 'publikasi'])->name('tampil.publikasi');
Route::get('/aspirasi-masyarakat', [FrontendController::class, 'aspirasi'])->name('tampil.aspirasi');

// Log monitoring (careful: restrict in production)
Route::get('/logs', [FrontendController::class, 'showLogs'])->name('logs.view');
Route::post('/peta-tematik/load/{id}', [FrontendController::class, 'lihatTematik'])->name('post.tematik');

// HALAMAN DETAIL //
Route::get('/proyek-strategis-daerah/{id}', [FrontendController::class, 'detailPeta'])->name('detail.psd');
Route::get('/proyek-strategis-nasional/{id}', [FrontendController::class, 'detailPeta'])->name('detail.psn');
Route::get('/peta-tematik/{id}', [FrontendController::class, 'detailPetaTematik'])->name('detail.tematik');
Route::get('/rpjmd/{id}', [FrontendController::class, 'detailPeta'])->name('detail.rpjmd');
Route::get('/pokir-dprd/{id}', [FrontendController::class, 'detailPeta'])->name('detail.pokir');
Route::get('/usulan-musrenbang/{id}', [FrontendController::class, 'detailPeta'])->name('detail.musrenbang');

// FAQ //
Route::get('/faq', function () {
    return view('frontend.pages.faq');
})->name('faq');

Route::get('/syarat-ketentuan', function () {
    return view('frontend.pages.syarat_ketentuan');
})->name('syarat_ketentuan');


Route::get('/kebijakan-privasi', function () {
    return view('frontend.pages.kebijakan_privasi');
})->name('kebijakan_privasi');

// FEEDBACK //
Route::post('/feedback-send', [FrontendController::class, 'store'])->name('feedback.store');

// USULAN ASPIRASI MASYARAKAT //
Route::post('/aspirasi-masyarakat', [FrontendController::class, 'aspirasiStore'])->name('aspirasi-masyarakat.store');

// API GEOJSON //
Route::get('/geojson', [FrontendController::class, 'getGeojsonByDataType']);


// Route::get('/visitors', [VisitorController::class, 'index'])->name('visitors.index');

require __DIR__ . '/auth.php';
require __DIR__ . '/backend.php';
