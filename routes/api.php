<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\ComplianceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Article routes
Route::apiResource('articles', ArticleController::class);
Route::post('articles/{article}/publish', [ArticleController::class, 'publishToFeed']);
Route::post('articles/{article}/publish-multiple', [ArticleController::class, 'publishToMultipleFeeds']);

// Feed management routes
Route::apiResource('feeds', FeedController::class);
Route::get('feeds/{feedConfig}/stats', [FeedController::class, 'getStats']);

// Compliance routes
Route::post('articles/{article}/check-compliance', [ComplianceController::class, 'checkCompliance']);
Route::get('compliance/rules', [ComplianceController::class, 'getRules']);
Route::post('compliance/rules', [ComplianceController::class, 'createRule']);
Route::put('compliance/rules/{rule}', [ComplianceController::class, 'updateRule']);
Route::get('compliance/validators', [ComplianceController::class, 'getAvailableValidators']);

// Health check and monitoring
Route::get('health', function () {
    $monitor = app(\App\Services\ComplianceMonitor::class);
    return response()->json($monitor->checkSystemHealth());
});