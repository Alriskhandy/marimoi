<?php

use App\Http\Controllers\Api\V1\LayerController;
use App\Http\Controllers\Api\V1\PublicationController;
use App\Http\Controllers\Api\V1\WebStatisticController;
use App\Http\Controllers\MalukuUtaraController;
use App\Http\Controllers\ProjectFeedbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Existing routes (non-versioned)
Route::apiResource('project-feedbacks', ProjectFeedbackController::class);
Route::post('project-feedbacks/{feedback}/respond', [ProjectFeedbackController::class, 'respond']);
Route::get('project-feedbacks-statistics', [ProjectFeedbackController::class, 'statistics']);
Route::get('project-feedbacks-location', [ProjectFeedbackController::class, 'byLocation']);

Route::prefix('maluku-utara')->group(function () {
    Route::get('reference', [MalukuUtaraController::class, 'getReferenceData']);
    Route::get('kecamatan/{kabupaten}', [MalukuUtaraController::class, 'getKecamatan']);
    Route::get('statistics', [MalukuUtaraController::class, 'getDetailedStatistics']);
    Route::get('maps-center', [MalukuUtaraController::class, 'getMapsCenter']);
    Route::get('validate/{kabupaten}/{kecamatan?}', [MalukuUtaraController::class, 'validateInput']);
});

// v1 API Routes (dilindungi API token)
Route::prefix('v1')
    ->middleware(['throttle:api-v1', 'api.logger', 'api.token'])
    ->group(function () {
        Route::get('layers', [LayerController::class, 'index']);
        Route::get('layers/{id}', [LayerController::class, 'show']);

        Route::get('publications', [PublicationController::class, 'index']);
        Route::get('publications/{id}', [PublicationController::class, 'show']);

        Route::get('web-statistics', [WebStatisticController::class, 'index']);
    });

