<?php

use Illuminate\Support\Facades\Route;

// ── Legacy routes — TANPA perubahan auth (backward compatibility frontend) ────
use App\Http\Controllers\ProjectFeedbackController;
use App\Http\Controllers\MalukuUtaraController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\LayerController;
use App\Http\Controllers\FeatureImageController;

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

Route::prefix('api')->group(function () {
    Route::prefix('features')->group(function () {
        Route::get('/uuid/{uuid}', [FeatureController::class, 'showByUuid']);
        Route::get('/layer/{layerId}', [FeatureController::class, 'getByLayer']);
        Route::get('/geojson/{layerId}', [FeatureController::class, 'getGeoJsonByLayer']);
        Route::get('/bounds', [FeatureController::class, 'getWithinBounds']);
        Route::get('/statistics/all', [FeatureController::class, 'getStatistics']);
        Route::get('/user/{userId}', [FeatureController::class, 'getByUser']);
        Route::get('/', [FeatureController::class, 'index']);
        Route::post('/', [FeatureController::class, 'store']);
        Route::post('/bulk', [FeatureController::class, 'bulkStore']);
        Route::get('/{id}', [FeatureController::class, 'show']);
        Route::put('/{id}', [FeatureController::class, 'update']);
        Route::delete('/{id}', [FeatureController::class, 'destroy']);
        Route::post('/{id}/views', [FeatureController::class, 'incrementViews']);
        Route::get('/{id}/images', [FeatureController::class, 'getImages']);
        Route::post('/{id}/images', [FeatureController::class, 'addImage']);
        Route::delete('/{featureId}/images/{imageId}', [FeatureController::class, 'removeImage']);
        Route::get('/{id}/with-images', [FeatureController::class, 'getWithImagesCount']);
    });

    Route::prefix('layers')->group(function () {
        Route::get('/roots', [LayerController::class, 'getRootLayers']);
        Route::get('/tree', [LayerController::class, 'getLayerTree']);
        Route::get('/active/all', [LayerController::class, 'getActiveLayers']);
        Route::get('/statistics/all', [LayerController::class, 'getStatistics']);
        Route::get('/user/{userId}', [LayerController::class, 'getByUser']);
        Route::get('/type/{type}', [LayerController::class, 'getByType']);
        Route::get('/category/{category}', [LayerController::class, 'getByCategory']);
        Route::get('/', [LayerController::class, 'index']);
        Route::post('/', [LayerController::class, 'store']);
        Route::post('/bulk', [LayerController::class, 'bulkStore']);
        Route::get('/{id}', [LayerController::class, 'show']);
        Route::put('/{id}', [LayerController::class, 'update']);
        Route::delete('/{id}', [LayerController::class, 'destroy']);
        Route::get('/{parentId}/children', [LayerController::class, 'getChildren']);
        Route::post('/{id}/toggle-active', [LayerController::class, 'toggleActive']);
        Route::post('/{id}/move', [LayerController::class, 'moveToParent']);
        Route::get('/{id}/with-features', [LayerController::class, 'getWithFeaturesCount']);
        Route::post('/{id}/duplicate', [LayerController::class, 'duplicate']);
    });

    Route::prefix('images')->group(function () {
        Route::get('/feature/{featureId}', [FeatureImageController::class, 'getByFeature']);
        Route::get('/layer/{layerId}', [FeatureImageController::class, 'getByLayer']);
        Route::get('/user/{userId}', [FeatureImageController::class, 'getByUser']);
        Route::get('/statistics/all', [FeatureImageController::class, 'getStatistics']);
        Route::get('/{id}/exists', [FeatureImageController::class, 'checkFileExists']);
        Route::get('/{id}/url', [FeatureImageController::class, 'getFileUrl']);
        Route::get('/', [FeatureImageController::class, 'index']);
        Route::post('/', [FeatureImageController::class, 'store']);
        Route::post('/upload/{featureId}', [FeatureImageController::class, 'upload']);
        Route::post('/bulk-upload/{featureId}', [FeatureImageController::class, 'bulkUpload']);
        Route::post('/bulk', [FeatureImageController::class, 'bulkStore']);
        Route::post('/cleanup', [FeatureImageController::class, 'cleanupOrphanedFiles']);
        Route::get('/{id}', [FeatureImageController::class, 'show']);
        Route::put('/{id}', [FeatureImageController::class, 'update']);
        Route::delete('/{id}', [FeatureImageController::class, 'destroy']);
    });
});

// ── Public API v1 — tanpa token (bebas akses) ────────────────────────────────
use App\Http\Controllers\Api\Public\PublicFeatureController;
use App\Http\Controllers\Api\Public\PublicLayerController;
use App\Http\Controllers\Api\Public\PublicStatisticController;
use App\Http\Controllers\Api\Public\PublicPublicationController;

Route::prefix('v1')->middleware(['client:public.read', 'client.type:public'])->group(function () {
    Route::get('layers/tree',  [PublicLayerController::class, 'getTree']);
    Route::get('layers',       [PublicLayerController::class, 'index']);
    Route::get('layers/{id}',  [PublicLayerController::class, 'show']);

    Route::get('count-visitors',    [PublicStatisticController::class, 'showCountVisitors']);
    Route::get('count-aspirations', [PublicStatisticController::class, 'showCountAspirations']);

    Route::get('publications',      [PublicPublicationController::class, 'index']);
    Route::get('publications/{id}', [PublicPublicationController::class, 'show']);
});

// ── Admin API v1 — wajib Bearer token dari Admin Client ──────────────────────
use App\Http\Controllers\Api\Admin\AdminFeatureController;
use App\Http\Controllers\Api\Admin\AdminLayerController;
use App\Http\Controllers\Api\Admin\AdminImageController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminPublicationController;
use App\Http\Controllers\Api\Admin\AdminFeedbackController;

Route::prefix('v1/admin')->middleware(['client.type:admin'])->group(function () {

    Route::middleware('client:admin.features')->group(function () {
        Route::get('features/statistics',  [AdminFeatureController::class, 'getStatistics']);
        Route::post('features/bulk',       [AdminFeatureController::class, 'bulkStore']);
        Route::apiResource('features',     AdminFeatureController::class);
    });

    Route::middleware('client:admin.layers')->group(function () {
        Route::get('layers/tree',                    [AdminLayerController::class, 'getTree']);
        Route::get('layers/statistics',              [AdminLayerController::class, 'getStatistics']);
        Route::post('layers/{id}/toggle-active',     [AdminLayerController::class, 'toggleActive']);
        Route::apiResource('layers',                 AdminLayerController::class);
    });

    Route::middleware('client:admin.images')->group(function () {
        Route::post('images/upload/{featureId}',      [AdminImageController::class, 'upload']);
        Route::post('images/bulk-upload/{featureId}', [AdminImageController::class, 'bulkUpload']);
        Route::apiResource('images',                  AdminImageController::class);
    });

    Route::middleware('client:admin.users')->group(function () {
        Route::apiResource('users', AdminUserController::class);
    });

    Route::middleware('client:admin.dashboard')->group(function () {
        Route::get('dashboard/statistics', [AdminDashboardController::class, 'statistics']);
        Route::get('dashboard/visitors',   [AdminDashboardController::class, 'getVisitorStatistics']);
    });

    Route::middleware('client:admin.publications')->group(function () {
        Route::apiResource('publications', AdminPublicationController::class);
    });

    Route::middleware('client:admin.aspirations')->group(function () {
        Route::put('feedbacks/{id}/respond', [AdminFeedbackController::class, 'respond']);
        Route::apiResource('feedbacks',      AdminFeedbackController::class);
    });
});
