<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ClaimModerationController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ItemModerationController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
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
Route::get('/cari', [ItemController::class, 'index'])->middleware('throttle:search')->name('items.index');
Route::get('/items/{item}', [ItemController::class, 'show'])->middleware('throttle:search')->name('items.show');

// Telegram calls this directly; it authenticates with a shared secret header
// rather than a session, so it must stay outside the web auth middleware.
Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->middleware('throttle:telegram-webhook')
    ->name('telegram.webhook');

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/lupa-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/lupa-password', [PasswordResetController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
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

    // Verifikasi email wajib sebelum melapor atau mengklaim. Dasbor dan
    // notifikasi sengaja tetap terbuka supaya pengguna tidak deadlock.
    Route::get('/email/verifikasi', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verifikasi/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verifikasi/kirim-ulang', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/konfirmasi-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/konfirmasi-password', [ConfirmablePasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.confirm.store');

    Route::get('/dasbor', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dasbor/laporan', [DashboardController::class, 'reports'])->name('dashboard.reports');
    Route::get('/dasbor/klaim', [DashboardController::class, 'claims'])->name('dashboard.claims');
    Route::delete('/klaim/{claim}', [DashboardController::class, 'cancelClaim'])->name('claims.cancel');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifikasi/{notification}', [NotificationController::class, 'show'])->name('notifications.show');

    Route::get('/pengaturan/telegram', [TelegramController::class, 'show'])->name('telegram.show');
    Route::delete('/pengaturan/telegram', [TelegramController::class, 'destroy'])->name('telegram.unlink');

    Route::get('/lapor/temuan', [ItemController::class, 'createFound'])->middleware('verified')->name('items.create-found');
    Route::post('/lapor/temuan', [ItemController::class, 'storeFound'])
        ->middleware(['verified', 'throttle:reports'])
        ->name('items.store-found');

    Route::get('/lapor/hilang', [ItemController::class, 'createLost'])->middleware('verified')->name('items.create-lost');
    Route::post('/lapor/hilang', [ItemController::class, 'storeLost'])
        ->middleware(['verified', 'throttle:reports'])
        ->name('items.store-lost');

    Route::post('/items/{item}/titip', [ItemController::class, 'confirmDeposit'])->middleware('verified')->name('items.confirm-deposit');
    Route::get('/lapor/{item}/ubah', [ItemController::class, 'edit'])->middleware('verified')->name('items.edit');
    Route::put('/lapor/{item}', [ItemController::class, 'update'])->middleware(['verified', 'throttle:reports'])->name('items.update');

    // Link a lost report to the found item it turned out to refer to.
    Route::get('/dasbor/laporan/{item}/kecocokan', [DashboardController::class, 'matches'])->middleware('verified')->name('dashboard.matches');
    Route::post('/dasbor/laporan/{item}/kecocokan', [DashboardController::class, 'storeMatch'])->middleware('verified')->name('dashboard.matches.store');
    Route::delete('/dasbor/laporan/{item}/kecocokan', [DashboardController::class, 'destroyMatch'])->middleware('verified')->name('dashboard.matches.destroy');

    Route::get('/items/{item}/klaim', [ClaimController::class, 'create'])->middleware('verified')->name('claims.create');
    Route::post('/items/{item}/klaim', [ClaimController::class, 'store'])
        ->middleware(['verified', 'throttle:claims'])
        ->name('claims.store');

    // Kode pickup setara barang fisik: wajib email terverifikasi dan
    // konfirmasi password sebelum halaman kode dibuka.
    Route::get('/klaim/{claim}/kode', [ClaimController::class, 'pickup'])
        ->middleware(['verified', 'password.confirm'])
        ->name('claims.pickup');
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
        Route::post('/titip/{item}/konfirmasi', [PickupVerificationController::class, 'confirmDeposit'])
            ->name('deposit.confirm');
        Route::post('/titip/konfirmasi-kode', [PickupVerificationController::class, 'confirmDepositByCode'])
            ->name('deposit.confirm-code');
        Route::get('/struk/{pickupCode}', [PickupVerificationController::class, 'receipt'])
            ->name('pickup.receipt');
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
        Route::get('/dasbor', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/analitik', [AnalyticsController::class, 'index'])->name('analytics');
        Route::get('/moderasi', [ItemModerationController::class, 'index'])->name('items.index');
        Route::get('/moderasi/export', [ItemModerationController::class, 'export'])->name('items.export');
        Route::get('/moderasi/{item}', [ItemModerationController::class, 'show'])->name('items.show');
        Route::get('/moderasi/{item}/foto', [ItemModerationController::class, 'photo'])->name('items.photo');
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
        Route::post('/kategori', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/kategori/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::post('/kategori/{category}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');

        Route::get('/lokasi', [LocationController::class, 'index'])->name('locations.index');
        Route::post('/lokasi', [LocationController::class, 'store'])->name('locations.store');
        Route::put('/lokasi/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::post('/lokasi/{location}/toggle', [LocationController::class, 'toggle'])->name('locations.toggle');

        Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::post('/pengguna/{user}/ban', [UserController::class, 'ban'])->name('users.ban');
        Route::post('/pengguna/{user}/unban', [UserController::class, 'unban'])->name('users.unban');
        Route::put('/pengguna/{user}/peran', [UserController::class, 'updateRole'])->name('users.role');
    });
