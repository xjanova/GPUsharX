<?php

use App\Http\Controllers\Admin\AdminAiController;
use App\Http\Controllers\Admin\AdminClientController;
use App\Http\Controllers\Admin\AdminReferralController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\ModelController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ClientController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckInstalled;
use Illuminate\Support\Facades\Route;

// Installation Routes (no middleware)
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::get('/step/{step}', [InstallController::class, 'step'])->name('step');
    Route::post('/step/{step}', [InstallController::class, 'processStep'])->name('process');
    Route::post('/test-db', [InstallController::class, 'testDatabase'])->name('test-db');
    Route::get('/uninstall', [InstallController::class, 'uninstall'])->name('uninstall');
});

// All other routes require installation
Route::middleware([CheckInstalled::class])->group(function () {

Route::get('/', function () {
    return view('welcome');
});

// Auth routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// User dashboard
Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard')->middleware('auth');

// Generation routes
Route::get('/generate', [GenerationController::class, 'index'])->name('generate');
Route::post('/generate', [GenerationController::class, 'create'])->name('generate.create')->middleware('auth');
Route::get('/generate/{jobId}', [GenerationController::class, 'status'])->name('generate.status');
Route::get('/generate/{jobId}/status', [GenerationController::class, 'checkStatus']);
Route::get('/gallery', [GenerationController::class, 'gallery'])->name('gallery');
Route::get('/my-generations', [GenerationController::class, 'myGenerations'])->name('my-generations')->middleware('auth');

// Model routes
Route::get('/models', [ModelController::class, 'index'])->name('models');
Route::get('/models/{modelId}', [ModelController::class, 'show'])->name('models.show');
Route::get('/models/{modelId}/install', [ModelController::class, 'installGuide'])->name('models.install');

// Referral routes
Route::middleware('auth')->group(function () {
    Route::get('/referral', [ReferralController::class, 'index'])->name('referral');
    Route::get('/referral/qr', [ReferralController::class, 'generateQr'])->name('referral.qr');
    Route::get('/referral/showcase', [ReferralController::class, 'showcase'])->name('referral.showcase');
    Route::post('/referral/showcase', [ReferralController::class, 'createShowcase'])->name('referral.showcase.create');
});

// Wallet routes
Route::middleware('auth')->prefix('wallet')->name('wallet.')->group(function () {
    Route::get('/', [WalletController::class, 'index'])->name('index');
    Route::post('/transfer', [WalletController::class, 'transferEarnings'])->name('transfer');
    Route::post('/withdraw', [WalletController::class, 'withdraw'])->name('withdraw');
    Route::post('/withdrawal/{withdrawal}/cancel', [WalletController::class, 'cancelWithdrawal'])->name('withdrawal.cancel');
    Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');
});

// Client download routes (public)
Route::get('/download', [ClientController::class, 'index'])->name('download');
Route::get('/download/{platform}', [ClientController::class, 'download'])->name('client.download');
Route::get('/download/{platform}/{version}', [ClientController::class, 'downloadVersion'])->name('client.download.file');

// Admin routes
Route::prefix('admin')
    ->middleware(['auth', AdminMiddleware::class])
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Users
        Route::get('/users', [DashboardController::class, 'users'])->name('users');
        Route::get('/users/{user}', [DashboardController::class, 'userDetail'])->name('users.detail');
        Route::patch('/users/{user}/status', [DashboardController::class, 'updateUserStatus'])->name('users.status');

        // Nodes
        Route::get('/nodes', [DashboardController::class, 'nodes'])->name('nodes');

        // Jobs
        Route::get('/jobs', [DashboardController::class, 'jobs'])->name('jobs');
        Route::get('/jobs/create', [DashboardController::class, 'createJob'])->name('jobs.create');
        Route::post('/jobs', [DashboardController::class, 'storeJob'])->name('jobs.store');

        // Payouts
        Route::get('/payouts', [DashboardController::class, 'payouts'])->name('payouts');
        Route::post('/payouts/{payout}/process', [DashboardController::class, 'processPayout'])->name('payouts.process');

        // Settings
        Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');

        // AI Models Management
        Route::get('/ai-models', [AdminAiController::class, 'aiModels'])->name('ai-models');
        Route::post('/ai-models/{model}/toggle-status', [AdminAiController::class, 'toggleModelStatus'])->name('ai-models.toggle-status');
        Route::post('/ai-models/{model}/toggle-featured', [AdminAiController::class, 'toggleModelFeatured'])->name('ai-models.toggle-featured');

        // Generations Management
        Route::get('/generations', [AdminAiController::class, 'generations'])->name('generations');
        Route::delete('/generations/{generation}', [AdminAiController::class, 'deleteGeneration'])->name('generations.delete');

        // Database Update Actions (AJAX)
        Route::post('/run-migrations', [AdminAiController::class, 'runMigrations'])->name('run-migrations');
        Route::post('/run-seeders', [AdminAiController::class, 'runSeeders'])->name('run-seeders');
        Route::post('/refresh-ai-models', [AdminAiController::class, 'refreshAiModels'])->name('refresh-ai-models');
        Route::get('/check-updates', [AdminAiController::class, 'checkUpdates'])->name('check-updates');

        // Referral Management
        Route::get('/referrals', [AdminReferralController::class, 'index'])->name('referrals');
        Route::get('/referral-settings', [AdminReferralController::class, 'referralSettings'])->name('referral-settings');
        Route::post('/referral-settings', [AdminReferralController::class, 'updateSettings'])->name('referral-settings.update');
        Route::get('/user-referrals', [AdminReferralController::class, 'userReferrals'])->name('user-referrals');
        Route::get('/referral-tree/{user}', [AdminReferralController::class, 'referralTree'])->name('referral-tree');
        Route::post('/referral-earnings/{earning}/pay', [AdminReferralController::class, 'payCommission'])->name('referral-earnings.pay');
        Route::post('/referral-earnings/bulk-pay', [AdminReferralController::class, 'bulkPayCommissions'])->name('referral-earnings.bulk-pay');

        // Client Version Management
        Route::get('/client-versions', [AdminClientController::class, 'index'])->name('client-versions');
        Route::post('/client-versions', [AdminClientController::class, 'store'])->name('client-versions.store');
        Route::put('/client-versions/{version}', [AdminClientController::class, 'update'])->name('client-versions.update');
        Route::delete('/client-versions/{version}', [AdminClientController::class, 'destroy'])->name('client-versions.destroy');
        Route::post('/client-versions/{version}/set-latest', [AdminClientController::class, 'setLatest'])->name('client-versions.set-latest');
        Route::post('/client-versions/{version}/toggle', [AdminClientController::class, 'toggle'])->name('client-versions.toggle');
    });

}); // End CheckInstalled middleware group
