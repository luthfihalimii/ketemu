<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ClaimModerationController;
use App\Http\Controllers\Admin\ItemModerationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Guard\PickupVerificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/cari', [ItemController::class, 'index'])->name('items.index');
Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');

// Telegram calls this directly; it authenticates with a shared secret header
// rather than a session, so it must stay outside the web auth middleware.
Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('telegram.webhook');

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/masuk', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');

    Route::get('/daftar', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/daftar', [RegisteredUserController::class, 'store'])->middleware('throttle:register');
});

/*
|--------------------------------------------------------------------------
| Authenticated student routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dasbor', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dasbor/laporan', [DashboardController::class, 'reports'])->name('dashboard.reports');
    Route::get('/dasbor/klaim', [DashboardController::class, 'claims'])->name('dashboard.claims');
    Route::delete('/klaim/{claim}', [DashboardController::class, 'cancelClaim'])->name('claims.cancel');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifikasi/{notification}', [NotificationController::class, 'show'])->name('notifications.show');

    Route::get('/pengaturan/telegram', [TelegramController::class, 'show'])->name('telegram.show');
    Route::delete('/pengaturan/telegram', [TelegramController::class, 'destroy'])->name('telegram.unlink');

    Route::get('/lapor/temuan', [ItemController::class, 'createFound'])->name('items.create-found');
    Route::post('/lapor/temuan', [ItemController::class, 'storeFound'])
        ->middleware('throttle:reports')
        ->name('items.store-found');

    Route::get('/lapor/hilang', [ItemController::class, 'createLost'])->name('items.create-lost');
    Route::post('/lapor/hilang', [ItemController::class, 'storeLost'])
        ->middleware('throttle:reports')
        ->name('items.store-lost');

    Route::post('/items/{item}/titip', [ItemController::class, 'confirmDeposit'])->name('items.confirm-deposit');

    // Link a lost report to the found item it turned out to refer to.
    Route::get('/dasbor/laporan/{item}/kecocokan', [DashboardController::class, 'matches'])->name('dashboard.matches');
    Route::post('/dasbor/laporan/{item}/kecocokan', [DashboardController::class, 'storeMatch'])->name('dashboard.matches.store');
    Route::delete('/dasbor/laporan/{item}/kecocokan', [DashboardController::class, 'destroyMatch'])->name('dashboard.matches.destroy');

    Route::get('/items/{item}/klaim', [ClaimController::class, 'create'])->name('claims.create');
    Route::post('/items/{item}/klaim', [ClaimController::class, 'store'])
        ->middleware('throttle:claims')
        ->name('claims.store');

    Route::get('/klaim/{claim}/kode', [ClaimController::class, 'pickup'])->name('claims.pickup');
});

/*
|--------------------------------------------------------------------------
| Security staff routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:guard,admin'])
    ->prefix('satpam')
    ->name('guard.')
    ->group(function () {
        Route::get('/verifikasi-kode', [PickupVerificationController::class, 'create'])->name('pickup.create');
        Route::post('/verifikasi-kode', [PickupVerificationController::class, 'store'])
            ->middleware('throttle:pickup-verification')
            ->name('pickup.store');
    });

/*
|--------------------------------------------------------------------------
| Admin moderation routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/moderasi', [ItemModerationController::class, 'index'])->name('items.index');
        Route::get('/moderasi/{item}', [ItemModerationController::class, 'show'])->name('items.show');
        Route::post('/moderasi/{item}/tandai', [ItemModerationController::class, 'flag'])->name('items.flag');
        Route::delete('/moderasi/{item}/tandai', [ItemModerationController::class, 'unflag'])->name('items.unflag');
        Route::post('/moderasi/{item}/nonaktifkan', [ItemModerationController::class, 'reject'])->name('items.reject');
        Route::post('/moderasi/{item}/pulihkan', [ItemModerationController::class, 'restore'])->name('items.restore');
        Route::post('/moderasi/{item}/kedaluwarsakan', [ItemModerationController::class, 'expire'])->name('items.expire');

        Route::get('/klaim', [ClaimModerationController::class, 'index'])->name('claims.index');
        Route::post('/klaim/{claim}/tolak', [ClaimModerationController::class, 'reject'])->name('claims.reject');
        Route::post('/klaim/{claim}/terbitkan-kode', [ClaimModerationController::class, 'reissueCode'])->name('claims.reissue');

        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');

        Route::get('/kategori', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/kategori/{category}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');

        Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
    });
