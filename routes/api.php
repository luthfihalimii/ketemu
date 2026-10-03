<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login');

    Route::get('/items', [ItemController::class, 'index'])->middleware('throttle:api-search')->name('items.index');
    Route::get('/items/{item}', [ItemController::class, 'show'])->middleware('throttle:api-search')->name('items.show');

    Route::middleware(['auth:sanctum', 'not-banned', 'throttle:api'])->group(function (): void {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});
