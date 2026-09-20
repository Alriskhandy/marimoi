<?php

use App\Http\Controllers\FrontendController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\PublicationDownloadController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

// HALAMAN //
Route::get('/', [FrontendController::class, 'indexDark'])->name('beranda');
Route::get('/profil-reformer', [FrontendController::class, 'reformer'])->name('tampil.reformer');
Route::get('/tentang', [FrontendController::class, 'tentang'])->name('tampil.tentang');
Route::get('/faq', [FrontendController::class, 'faq'])->name('tampil.faq');
// Halaman lama digabung ke Peta Tematik //
foreach (['proyek-strategis-daerah', 'proyek-strategis-nasional', 'usulan-musrenbang', 'pokir-dprd'] as $halamanLama) {
    Route::redirect('/'.$halamanLama, '/peta-tematik', 301);
}
Route::get('/prioritas-daerah', [FrontendController::class, 'prioritas'])->name('tampil.prioritas');
Route::get('/peta-tematik', [FrontendController::class, 'tematik'])->name('tampil.tematik');
Route::get('/dokumen-publikasi', [FrontendController::class, 'publikasi'])->name('tampil.publikasi');
Route::get('/aspirasi-masyarakat', [FrontendController::class, 'aspirasi'])->name('tampil.aspirasi');

Route::post('/peta-tematik/load/{id}', [FrontendController::class, 'lihatTematik'])->name('post.tematik');

// SHARE PETA (link unik untuk kombinasi layer + viewport peta) //
Route::post('/peta-tematik/share', [FrontendController::class, 'createSharedMap'])->name('tematik.share.store');
Route::get('/peta-tematik/share/{slug}', [FrontendController::class, 'showSharedMap'])->name('tematik.share.show');

// HALAMAN DETAIL //
Route::get('/proyek-strategis-daerah/{id}', [FrontendController::class, 'detailPeta'])->name('detail.psd');
Route::get('/proyek-strategis-nasional/{id}', [FrontendController::class, 'detailPeta'])->name('detail.psn');
Route::get('/peta-tematik/{id}', [FrontendController::class, 'detailPetaTematik'])->name('detail.tematik');
Route::get('/rpjmd/{id}', [FrontendController::class, 'detailPeta'])->name('detail.rpjmd');
Route::get('/pokir-dprd/{id}', [FrontendController::class, 'detailPeta'])->name('detail.pokir');
Route::get('/usulan-musrenbang/{id}', [FrontendController::class, 'detailPeta'])->name('detail.musrenbang');

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

// LACAK STATUS ASPIRASI //
Route::get('/aspirasi-masyarakat/lacak', [FrontendController::class, 'aspirasiLacak'])->name('aspirasi-masyarakat.lacak');
Route::post('/aspirasi-masyarakat/lacak', [FrontendController::class, 'aspirasiLacakCari'])
    ->name('aspirasi-masyarakat.lacak.cari')
    ->middleware('throttle:6,1');

// API GEOJSON //
Route::get('/geojson', [FrontendController::class, 'getGeojsonByDataType']);
Route::get('/geojson/version', [FrontendController::class, 'tematikVersion'])->name('tematik.version');

// Route::get('/visitors', [VisitorController::class, 'index'])->name('visitors.index');

// Public routes - Publications
Route::prefix('dokumen-publikasi')->group(function () {
    Route::get('/', [PublicationController::class, 'index'])->name('tampil.publikasi');
    Route::post('/{publication}/download', [PublicationDownloadController::class, 'processSurveyAndDownload'])->name('download.publikasi');
});

// Public routes - Survey (untuk guest)
// Route::prefix('survey')->name('survey.')->group(function () {
//     Route::get('/', [SurveyController::class, 'showGeneralSurveyForm'])->name('form');
//     Route::post('/', [SurveyController::class, 'submitGeneralSurvey'])->name('submit');
//     Route::get('/thank-you', [SurveyController::class, 'thankYou'])->name('thank-you');
// });

require __DIR__.'/auth.php';
require __DIR__.'/backend.php';
