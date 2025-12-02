<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EarningController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\NodeController;
use App\Http\Controllers\Api\WorkerController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Pool statistics (public)
Route::get('/pool/stats', [JobController::class, 'getPoolStats']);

// Public leaderboard and rank info
Route::get('/workers/leaderboard', [WorkerController::class, 'getLeaderboard']);
Route::get('/workers/ranks', [WorkerController::class, 'getRankInfo']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Node management
    Route::prefix('nodes')->group(function () {
        Route::get('/', [NodeController::class, 'list']);
        Route::post('/register', [NodeController::class, 'register']);
        Route::post('/heartbeat', [NodeController::class, 'heartbeat']);
        Route::post('/benchmark', [NodeController::class, 'submitBenchmark']);
        Route::post('/verification', [NodeController::class, 'submitVerification']);
        Route::post('/disconnect', [NodeController::class, 'disconnect']);
    });

    // Job/Work management
    Route::prefix('jobs')->group(function () {
        Route::get('/work', [JobController::class, 'getWork']);
        Route::post('/start', [JobController::class, 'startWork']);
        Route::post('/progress', [JobController::class, 'updateProgress']);
        Route::post('/submit', [JobController::class, 'submitWork']);
        Route::post('/error', [JobController::class, 'reportError']);
        // Parallel Processing endpoints
        Route::post('/upload-partial', [JobController::class, 'uploadPartialResult']);
        Route::get('/{jobId}/progress', [JobController::class, 'getJobProgress']);
    });

    // Earnings and payouts
    Route::prefix('earnings')->group(function () {
        Route::get('/', [EarningController::class, 'index']);
        Route::get('/summary', [EarningController::class, 'summary']);
    });

    Route::prefix('payouts')->group(function () {
        Route::post('/request', [EarningController::class, 'requestPayout']);
        Route::get('/history', [EarningController::class, 'payoutHistory']);
        Route::get('/{payoutId}', [EarningController::class, 'payoutStatus']);
    });

    // Worker Performance
    Route::prefix('workers')->group(function () {
        Route::get('/my-workers', [WorkerController::class, 'getAllWorkersPerformance']);
        Route::get('/performance', [WorkerController::class, 'getPerformance']);
        Route::get('/performance/history', [WorkerController::class, 'getPerformanceHistory']);
        Route::post('/evaluate', [WorkerController::class, 'requestEvaluation']);
        Route::get('/distribution-stats', [WorkerController::class, 'getDistributionStats']);
    });
});
