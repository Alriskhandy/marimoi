<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectFeedbackController;
use App\Http\Controllers\MalukuUtaraController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\LayerController;
use App\Http\Controllers\FeatureImageController;


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

    // GIS Features API Routes
    Route::prefix('api')->group(function () {

        // Features Routes
        Route::prefix('features')->group(function () {
            Route::get('/', [FeatureController::class, 'index']);
            Route::post('/', [FeatureController::class, 'store']);
            Route::get('/uuid/{uuid}', [FeatureController::class, 'showByUuid']);
            Route::get('/{id}', [FeatureController::class, 'show']);
            Route::put('/{id}', [FeatureController::class, 'update']);
            Route::delete('/{id}', [FeatureController::class, 'destroy']);

            // Additional Feature Routes
            Route::get('/layer/{layerId}', [FeatureController::class, 'getByLayer']);
            Route::get('/user/{userId}', [FeatureController::class, 'getByUser']);
            Route::get('/bounds', [FeatureController::class, 'getWithinBounds']);
            Route::post('/{id}/views', [FeatureController::class, 'incrementViews']);
            Route::get('/{id}/images', [FeatureController::class, 'getImages']);
            Route::post('/{id}/images', [FeatureController::class, 'addImage']);
            Route::delete('/{featureId}/images/{imageId}', [FeatureController::class, 'removeImage']);
            Route::get('/{id}/with-images', [FeatureController::class, 'getWithImagesCount']);
            Route::get('/statistics/all', [FeatureController::class, 'getStatistics']);
            Route::post('/bulk', [FeatureController::class, 'bulkStore']);
        });

        // Layers Routes
        Route::prefix('layers')->group(function () {
            // Static routes must come before /{id} to avoid being captured as a parameter
            Route::get('/roots', [LayerController::class, 'getRootLayers']);
            Route::get('/tree', [LayerController::class, 'getLayerTree']);
            Route::get('/active/all', [LayerController::class, 'getActiveLayers']);
            Route::get('/statistics/all', [LayerController::class, 'getStatistics']);
            Route::get('/user/{userId}', [LayerController::class, 'getByUser']);
            Route::get('/type/{type}', [LayerController::class, 'getByType']);
            Route::post('/bulk', [LayerController::class, 'bulkStore']);

            Route::get('/', [LayerController::class, 'index']);
            Route::post('/', [LayerController::class, 'store']);
            Route::get('/{id}', [LayerController::class, 'show']);
            Route::put('/{id}', [LayerController::class, 'update']);
            Route::delete('/{id}', [LayerController::class, 'destroy']);
            Route::get('/{parentId}/children', [LayerController::class, 'getChildren']);
            Route::post('/{id}/toggle-active', [LayerController::class, 'toggleActive']);
            Route::post('/{id}/move', [LayerController::class, 'moveToParent']);
            Route::get('/{id}/with-features', [LayerController::class, 'getWithFeaturesCount']);
            Route::post('/{id}/duplicate', [LayerController::class, 'duplicate']);
        });

        // Feature Images Routes
        Route::prefix('images')->group(function () {
            Route::get('/', [FeatureImageController::class, 'index']);
            Route::post('/', [FeatureImageController::class, 'store']);
            Route::get('/{id}', [FeatureImageController::class, 'show']);
            Route::put('/{id}', [FeatureImageController::class, 'update']);
            Route::delete('/{id}', [FeatureImageController::class, 'destroy']);

            // Additional Image Routes
            Route::get('/feature/{featureId}', [FeatureImageController::class, 'getByFeature']);
            Route::get('/layer/{layerId}', [FeatureImageController::class, 'getByLayer']);
            Route::get('/user/{userId}', [FeatureImageController::class, 'getByUser']);
            Route::post('/upload/{featureId}', [FeatureImageController::class, 'upload']);
            Route::post('/bulk-upload/{featureId}', [FeatureImageController::class, 'bulkUpload']);
            Route::post('/bulk', [FeatureImageController::class, 'bulkStore']);
            Route::get('/{id}/exists', [FeatureImageController::class, 'checkFileExists']);
            Route::get('/{id}/url', [FeatureImageController::class, 'getFileUrl']);
            Route::get('/statistics/all', [FeatureImageController::class, 'getStatistics']);
            Route::post('/cleanup', [FeatureImageController::class, 'cleanupOrphanedFiles']);
        });
    });

});



// routes/web.php
