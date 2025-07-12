<?php

use App\Http\Controllers\FrontendController;
use App\Http\Controllers\KategoriLayerController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\MalukuUtaraController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectFeedbackController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontendController::class, 'index'])->name('beranda');
Route::get('/peta-gis', [FrontendController::class, 'showMap'])->name('tampil.peta');

require __DIR__.'/auth.php';
require __DIR__.'/backend.php';
