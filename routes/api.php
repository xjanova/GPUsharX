<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EarningController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\ModelController;
use App\Http\Controllers\Api\NodeController;
use App\Http\Controllers\Api\WorkerController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\Admin\AnalyticsController;
use App\Http\Controllers\Api\Admin\ModelManagementController;
use App\Http\Controllers\Api\Admin\PackageController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Pool statistics (public)
Route::get('/pool/stats', [JobController::class, 'getPoolStats']);

// Public leaderboard and rank info
Route::get('/workers/leaderboard', [WorkerController::class, 'getLeaderboard']);
Route::get('/workers/ranks', [WorkerController::class, 'getRankInfo']);

// Public AI Models info
Route::get('/models', [ModelController::class, 'getAllModels']);
Route::get('/models/{modelId}', [ModelController::class, 'getModel']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // User Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [UserProfileController::class, 'show']);
        Route::put('/', [UserProfileController::class, 'update']);
        Route::post('/avatar', [UserProfileController::class, 'updateAvatar']);
        Route::post('/change-password', [UserProfileController::class, 'changePassword']);
        Route::get('/purchases', [UserProfileController::class, 'purchaseHistory']);
        Route::get('/jobs', [UserProfileController::class, 'jobHistory']);
        Route::get('/notifications', [UserProfileController::class, 'getNotificationSettings']);
        Route::put('/notifications', [UserProfileController::class, 'updateNotificationSettings']);
        Route::get('/tokens', [UserProfileController::class, 'getApiTokens']);
        Route::post('/tokens', [UserProfileController::class, 'createApiToken']);
        Route::delete('/tokens/{tokenId}', [UserProfileController::class, 'revokeApiToken']);
        Route::delete('/account', [UserProfileController::class, 'deleteAccount']);
    });

    // Referrals
    Route::prefix('referrals')->group(function () {
        Route::get('/tree', [ReferralController::class, 'getMyReferralTree']);
        Route::get('/graph', [ReferralController::class, 'getGraphData']);
    });

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

    // AI Models management
    Route::prefix('models')->group(function () {
        Route::get('/available', [ModelController::class, 'getAvailableModels']);
        Route::get('/installed', [ModelController::class, 'getInstalledModels']);
        Route::get('/{modelId}/download', [ModelController::class, 'getDownloadInstructions']);
        Route::post('/install', [ModelController::class, 'registerInstallation']);
        Route::post('/uninstall', [ModelController::class, 'unregisterInstallation']);
    });

    // =========================================================================
    // ADMIN ROUTES
    // =========================================================================
    Route::prefix('admin')->middleware('admin')->group(function () {
        // Analytics
        Route::prefix('analytics')->group(function () {
            Route::get('/dashboard', [AnalyticsController::class, 'dashboard']);
            Route::get('/models', [AnalyticsController::class, 'modelAnalytics']);
            Route::get('/daily', [AnalyticsController::class, 'dailyAnalytics']);
            Route::get('/nodes', [AnalyticsController::class, 'nodeAnalytics']);
            Route::get('/users', [AnalyticsController::class, 'userAnalytics']);
            Route::get('/revenue', [AnalyticsController::class, 'revenueAnalytics']);
            Route::get('/realtime', [AnalyticsController::class, 'realtimeStats']);
        });

        // Model Management
        Route::prefix('models')->group(function () {
            Route::get('/', [ModelManagementController::class, 'index']);
            Route::post('/', [ModelManagementController::class, 'store']);
            Route::get('/{modelId}', [ModelManagementController::class, 'show']);
            Route::put('/{modelId}', [ModelManagementController::class, 'update']);
            Route::delete('/{modelId}', [ModelManagementController::class, 'destroy']);
            Route::post('/{modelId}/toggle-active', [ModelManagementController::class, 'toggleActive']);
            Route::post('/{modelId}/toggle-featured', [ModelManagementController::class, 'toggleFeatured']);
            Route::post('/bulk-update', [ModelManagementController::class, 'bulkUpdate']);
        });

        // Package Management
        Route::prefix('packages')->group(function () {
            Route::get('/', [PackageController::class, 'index']);
            Route::post('/', [PackageController::class, 'store']);
            Route::put('/{package}', [PackageController::class, 'update']);
            Route::delete('/{package}', [PackageController::class, 'destroy']);
            Route::post('/{package}/toggle-active', [PackageController::class, 'toggleActive']);
            Route::post('/seed-defaults', [PackageController::class, 'seedDefaults']);
            Route::get('/sales-stats', [PackageController::class, 'salesStats']);
        });

        // Referral Management
        Route::prefix('referrals')->group(function () {
            Route::get('/network', [ReferralController::class, 'getAllReferralNetwork']);
            Route::get('/user/{userId}', [ReferralController::class, 'getUserReferralTree']);
        });
    });
});
