<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectFeedbackController;
use App\Http\Controllers\MalukuUtaraController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('api')->group(function () {
    
    // Project Feedback Routes
    Route::apiResource('project-feedbacks', ProjectFeedbackController::class);
    Route::post('project-feedbacks/{feedback}/respond', [ProjectFeedbackController::class, 'respond']);
    Route::get('project-feedbacks-statistics', [ProjectFeedbackController::class, 'statistics']);
    Route::get('project-feedbacks-location', [ProjectFeedbackController::class, 'byLocation']);

    // Maluku Utara Reference Routes
    Route::prefix('maluku-utara')->group(function () {
        Route::get('reference', [MalukuUtaraController::class, 'getReferenceData']);
        Route::get('kecamatan/{kabupaten}', [MalukuUtaraController::class, 'getKecamatan']);
        Route::get('statistics', [MalukuUtaraController::class, 'getDetailedStatistics']);
        Route::get('maps-center', [MalukuUtaraController::class, 'getMapsCenter']);
        Route::get('validate/{kabupaten}/{kecamatan?}', [MalukuUtaraController::class, 'validateInput']);
    });

});