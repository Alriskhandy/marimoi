<?php

use App\Http\Controllers\FrontendController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\PublicationDownloadController;
use App\Http\Controllers\SpatialMapController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

// HALAMAN //
Route::get('/', [FrontendController::class, 'indexDark'])->name('beranda');
Route::get('/tentang', [FrontendController::class, 'tentang'])->name('tampil.tentang');
// Profil Reformer kini menjadi bagian halaman Tentang //
Route::permanentRedirect('/profil-reformer', '/tentang#profil-reformer');
Route::get('/faq', [FrontendController::class, 'faq'])->name('tampil.faq');
// Halaman lama digabung ke Peta Interaktif //
foreach (['proyek-strategis-daerah', 'proyek-strategis-nasional', 'usulan-musrenbang', 'pokir-dprd'] as $halamanLama) {
    Route::redirect('/'.$halamanLama, '/peta-interaktif', 301);
}
Route::get('/prioritas-daerah', [FrontendController::class, 'prioritas'])->name('tampil.prioritas');
Route::get('/peta-interaktif', [FrontendController::class, 'tematik'])->name('tampil.interaktif');
Route::get('/dokumen-publikasi', [FrontendController::class, 'publikasi'])->name('tampil.publikasi');
Route::get('/aspirasi-masyarakat', [FrontendController::class, 'aspirasi'])->name('tampil.aspirasi');

Route::post('/peta-interaktif/load/{id}', [FrontendController::class, 'lihatTematik'])->name('post.interaktif');

// SHARE PETA (link unik untuk kombinasi layer + viewport peta) //
Route::post('/peta-interaktif/share', [FrontendController::class, 'createSharedMap'])->name('interaktif.share.store');
Route::get('/peta-interaktif/share/{slug}', [FrontendController::class, 'showSharedMap'])->name('interaktif.share.show');

// URL lama /peta-tematik (bookmark, link share, hasil mesin pencari) dialihkan permanen //
Route::permanentRedirect('/peta-tematik', '/peta-interaktif');
Route::permanentRedirect('/peta-tematik/share/{slug}', '/peta-interaktif/share/{slug}');
Route::permanentRedirect('/peta-tematik/{id}', '/peta-interaktif/{id}');

// HALAMAN DETAIL //
Route::get('/proyek-strategis-daerah/{id}', [FrontendController::class, 'detailPeta'])->name('detail.psd');
Route::get('/proyek-strategis-nasional/{id}', [FrontendController::class, 'detailPeta'])->name('detail.psn');
Route::get('/peta-interaktif/{id}', [FrontendController::class, 'detailPetaTematik'])->name('detail.interaktif');
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
// Halaman lacak sudah digabung ke halaman Aspirasi (bagian #lacak) //
Route::get('/aspirasi-masyarakat/lacak', fn () => redirect()->to(route('tampil.aspirasi').'#lacak', 301));
Route::post('/aspirasi-masyarakat/lacak', [FrontendController::class, 'aspirasiLacakCari'])
    ->name('aspirasi-masyarakat.lacak.cari')
    ->middleware('throttle:6,1');

// API GEOJSON //
Route::get('/geojson', [FrontendController::class, 'getGeojsonByDataType']);
Route::get('/geojson/version', [FrontendController::class, 'tematikVersion'])->name('interaktif.version');
Route::get('/geojson/filter-options', [FrontendController::class, 'getFilterOptions'])->name('interaktif.filter-options');
Route::get('/geojson/filter-categories', [FrontendController::class, 'getFilterCategories'])->name('interaktif.filter-categories');

// GEOJSON LAYER V2 (dipakai preview peta di halaman admin spatial-layers/show) //
Route::prefix('peta-v2')->name('peta-v2.')->group(function () {
    Route::get('/geojson/{layer:slug}', [SpatialMapController::class, 'geojson'])->name('geojson');
});

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
