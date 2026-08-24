<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Category\CategoryController;
use App\Http\Controllers\Api\V1\Dashboard\DashboardController;
use App\Http\Controllers\Api\V1\Notification\NotificationController;
use App\Http\Controllers\Api\V1\Transaction\TransactionController;
use App\Http\Controllers\Api\V1\Transfer\TransferController;
use App\Http\Controllers\Api\V1\Wallet\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:6,1,login')
            ->name('login');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:6,1,forgot-password')
            ->name('forgot-password');
        Route::post('verify-otp', [AuthController::class, 'verifyOtp'])
            ->middleware('throttle:6,1,verify-otp')
            ->name('verify-otp');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me', [AuthController::class, 'me'])->name('me');
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('wallets', WalletController::class);
        Route::apiResource('transactions', TransactionController::class);
        Route::apiResource('transfers', TransferController::class)->only(['index', 'store', 'show']);
        Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);

        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('summary', [DashboardController::class, 'summary'])->name('summary');
            Route::get('chart', [DashboardController::class, 'chart'])->name('chart');
        });

        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::post('read-all', [NotificationController::class, 'readAll'])->name('read-all');
            Route::post('{notification}/read', [NotificationController::class, 'read'])->name('read');
        });
    });
});
