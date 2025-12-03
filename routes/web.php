<?php

use App\Http\Controllers\Admin\AdminAiController;
use App\Http\Controllers\Admin\AdminClientController;
use App\Http\Controllers\Admin\AdminReferralController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KycController as AdminKycController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\ModelController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\PaymentController;
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

// Investor Pitch Page
Route::get('/pitch', function () {
    return view('pitch');
})->name('pitch');

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
Route::get('/my-generations', [GenerationController::class, 'myGenerations'])->name('generate.my')->middleware('auth');

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

// Google Drive Settings
Route::middleware('auth')->prefix('settings')->name('settings.')->group(function () {
    Route::get('/google-drive', [GoogleDriveController::class, 'settings'])->name('google-drive');
    Route::get('/google-drive/connect', [GoogleDriveController::class, 'connect'])->name('google-drive.connect');
    Route::get('/google-drive/callback', [GoogleDriveController::class, 'callback'])->name('google-drive.callback');
    Route::delete('/google-drive/disconnect', [GoogleDriveController::class, 'disconnect'])->name('google-drive.disconnect');
    Route::post('/google-drive/test', [GoogleDriveController::class, 'testUpload'])->name('google-drive.test');
});

// Credits & Payment routes
Route::middleware('auth')->prefix('credits')->name('credits.')->group(function () {
    Route::get('/buy', [PaymentController::class, 'index'])->name('buy');
    Route::get('/history', [PaymentController::class, 'history'])->name('history');
});

Route::middleware('auth')->prefix('payment')->name('payment.')->group(function () {
    Route::post('/checkout', [PaymentController::class, 'createCheckoutSession'])->name('checkout');
    Route::get('/success', [PaymentController::class, 'success'])->name('success');
    Route::get('/cancel', [PaymentController::class, 'cancel'])->name('cancel');
    Route::get('/manual/{package}', [PaymentController::class, 'manualPayment'])->name('manual');
    Route::post('/confirm', [PaymentController::class, 'confirmManualPayment'])->name('confirm');
    Route::get('/pending', [PaymentController::class, 'pendingPayments'])->name('pending');
});

// Stripe Webhook (no auth, no CSRF)
Route::post('/webhook/stripe', [PaymentController::class, 'webhook'])->name('webhook.stripe');

// KYC Verification routes
Route::middleware('auth')->prefix('kyc')->name('kyc.')->group(function () {
    Route::get('/', [KycController::class, 'index'])->name('index');
    Route::get('/verify', [KycController::class, 'create'])->name('create');
    Route::post('/verify', [KycController::class, 'store'])->name('store');
    Route::get('/document/{type}', [KycController::class, 'viewDocument'])->name('document');
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
        Route::post('/users', [DashboardController::class, 'createUser'])->name('users.store');
        Route::put('/users/{user}', [DashboardController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [DashboardController::class, 'deleteUser'])->name('users.delete');
        Route::post('/users/{user}/ban', [DashboardController::class, 'banUser'])->name('users.ban');
        Route::post('/users/{user}/unban', [DashboardController::class, 'unbanUser'])->name('users.unban');
        Route::post('/users/{user}/credits/add', [DashboardController::class, 'addCredits'])->name('users.credits.add');
        Route::post('/users/{user}/credits/deduct', [DashboardController::class, 'deductCredits'])->name('users.credits.deduct');
        Route::post('/users/{user}/credits/set', [DashboardController::class, 'setCredits'])->name('users.credits.set');

        // Nodes
        Route::get('/nodes', [DashboardController::class, 'nodes'])->name('nodes');
        Route::get('/nodes/{node}', [DashboardController::class, 'nodeDetail'])->name('nodes.detail');
        Route::patch('/nodes/{node}/status', [DashboardController::class, 'updateNodeStatus'])->name('nodes.status');
        Route::post('/nodes/{node}/ban', [DashboardController::class, 'banNode'])->name('nodes.ban');
        Route::post('/nodes/{node}/unban', [DashboardController::class, 'unbanNode'])->name('nodes.unban');
        Route::delete('/nodes/{node}', [DashboardController::class, 'deleteNode'])->name('nodes.delete');

        // Jobs
        Route::get('/jobs', [DashboardController::class, 'jobs'])->name('jobs');
        Route::get('/jobs/create', [DashboardController::class, 'createJob'])->name('jobs.create');
        Route::post('/jobs', [DashboardController::class, 'storeJob'])->name('jobs.store');
        Route::post('/jobs/{job}/cancel', [DashboardController::class, 'cancelJob'])->name('jobs.cancel');
        Route::post('/jobs/{job}/force-fail', [DashboardController::class, 'forceFailJob'])->name('jobs.force-fail');
        Route::post('/jobs/{job}/retry', [DashboardController::class, 'retryJob'])->name('jobs.retry');

        // Credit History
        Route::get('/credit-history', [DashboardController::class, 'creditHistory'])->name('credit-history');

        // Payouts
        Route::get('/payouts', [DashboardController::class, 'payouts'])->name('payouts');
        Route::post('/payouts/{payout}/process', [DashboardController::class, 'processPayout'])->name('payouts.process');

        // Settings
        Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
        Route::put('/settings', [DashboardController::class, 'updateSettings'])->name('settings.update');
        Route::post('/settings/delete-image', [DashboardController::class, 'deleteSettingImage'])->name('settings.delete-image');
        Route::put('/settings/api-keys', [DashboardController::class, 'updateApiKeys'])->name('settings.api-keys');
        Route::post('/settings/test-huggingface', [DashboardController::class, 'testHuggingFaceToken'])->name('settings.test-huggingface');

        // Payment Settings
        Route::get('/payment-settings', [DashboardController::class, 'paymentSettings'])->name('payment-settings');
        Route::put('/payment-settings', [DashboardController::class, 'updatePaymentSettings'])->name('payment-settings.update');
        Route::post('/payment-settings/test', [DashboardController::class, 'testPaymentConnection'])->name('payment-settings.test');

        // Manual Payments Management
        Route::get('/manual-payments', [DashboardController::class, 'manualPayments'])->name('manual-payments');
        Route::get('/manual-payments/{payment}', [DashboardController::class, 'manualPaymentDetail'])->name('manual-payments.detail');
        Route::post('/manual-payments/{payment}/approve', [DashboardController::class, 'approveManualPayment'])->name('manual-payments.approve');
        Route::post('/manual-payments/{payment}/reject', [DashboardController::class, 'rejectManualPayment'])->name('manual-payments.reject');

        // AI Models Management
        Route::get('/ai-models', [AdminAiController::class, 'aiModels'])->name('ai-models');
        Route::get('/ai-models/{model}/edit', [AdminAiController::class, 'editModel'])->name('ai-models.edit');
        Route::put('/ai-models/{model}', [AdminAiController::class, 'updateModel'])->name('ai-models.update');
        Route::post('/ai-models/{model}/toggle-status', [AdminAiController::class, 'toggleModelStatus'])->name('ai-models.toggle-status');
        Route::post('/ai-models/{model}/toggle-featured', [AdminAiController::class, 'toggleModelFeatured'])->name('ai-models.toggle-featured');
        Route::post('/ai-models/{model}/upload-thumbnail', [AdminAiController::class, 'uploadThumbnail'])->name('ai-models.upload-thumbnail');

        // Model Store (HuggingFace)
        Route::get('/model-store', [AdminAiController::class, 'modelStore'])->name('model-store');
        Route::post('/model-store/search', [AdminAiController::class, 'searchHuggingFace'])->name('model-store.search');
        Route::post('/model-store/import', [AdminAiController::class, 'importModel'])->name('model-store.import');

        // Analytics Dashboard
        Route::get('/analytics', [AdminAiController::class, 'analytics'])->name('analytics');

        // Playground - Test AI Models
        Route::get('/playground', [AdminAiController::class, 'playground'])->name('playground');
        Route::post('/playground/run', [AdminAiController::class, 'runTest'])->name('playground.run');
        Route::get('/playground/status/{jobId}', [AdminAiController::class, 'testStatus'])->name('playground.status');

        // Packages Management
        Route::get('/packages', [AdminAiController::class, 'packages'])->name('packages');
        Route::post('/packages', [AdminAiController::class, 'storePackage'])->name('packages.store');
        Route::put('/packages/{package}', [AdminAiController::class, 'updatePackage'])->name('packages.update');
        Route::delete('/packages/{package}', [AdminAiController::class, 'deletePackage'])->name('packages.delete');
        Route::post('/packages/{package}/toggle', [AdminAiController::class, 'togglePackage'])->name('packages.toggle');

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
        Route::get('/referral-tree', [AdminReferralController::class, 'referralTreeView'])->name('referral-tree');
        Route::get('/referral-tree/{user}', [AdminReferralController::class, 'referralTree'])->name('referral-tree.user');
        Route::post('/referral-earnings/{earning}/pay', [AdminReferralController::class, 'payCommission'])->name('referral-earnings.pay');
        Route::post('/referral-earnings/bulk-pay', [AdminReferralController::class, 'bulkPayCommissions'])->name('referral-earnings.bulk-pay');

        // Client Version Management
        Route::get('/client-versions', [AdminClientController::class, 'index'])->name('client-versions');
        Route::post('/client-versions', [AdminClientController::class, 'store'])->name('client-versions.store');
        Route::put('/client-versions/{version}', [AdminClientController::class, 'update'])->name('client-versions.update');
        Route::delete('/client-versions/{version}', [AdminClientController::class, 'destroy'])->name('client-versions.destroy');
        Route::post('/client-versions/{version}/set-latest', [AdminClientController::class, 'setLatest'])->name('client-versions.set-latest');
        Route::post('/client-versions/{version}/toggle', [AdminClientController::class, 'toggle'])->name('client-versions.toggle');

        // KYC Management
        Route::get('/kyc', [AdminKycController::class, 'index'])->name('kyc.index');
        Route::get('/kyc/{kyc}', [AdminKycController::class, 'show'])->name('kyc.show');
        Route::post('/kyc/{kyc}/approve', [AdminKycController::class, 'approve'])->name('kyc.approve');
        Route::post('/kyc/{kyc}/reject', [AdminKycController::class, 'reject'])->name('kyc.reject');
        Route::get('/kyc/{kyc}/document/{type}', [AdminKycController::class, 'viewDocument'])->name('kyc.document');

        // VRAM Management
        Route::get('/vram', [\App\Http\Controllers\Admin\VramController::class, 'index'])->name('vram');
        Route::post('/vram/settings', [\App\Http\Controllers\Admin\VramController::class, 'updateSettings'])->name('vram.update-settings');
        Route::get('/vram/stats', [\App\Http\Controllers\Admin\VramController::class, 'getStats'])->name('vram.stats');
        Route::get('/vram/workers/{tier}', [\App\Http\Controllers\Admin\VramController::class, 'getWorkersByTier'])->name('vram.workers-by-tier');
        Route::post('/vram/resplit/{job}', [\App\Http\Controllers\Admin\VramController::class, 'resplitJob'])->name('vram.resplit');
    });

}); // End CheckInstalled middleware group
