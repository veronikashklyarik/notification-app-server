<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\HistoryController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('register', [RegisterController::class, 'store'])->name('api.v1.auth.register');
        Route::post('login', [LoginController::class, 'store'])->name('api.v1.auth.login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::delete('logout', [LogoutController::class, 'destroy'])->name('api.v1.auth.logout');
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('profile', [ProfileController::class, 'show'])->name('api.v1.profile');

        Route::apiResource('notifications', NotificationController::class)->names([
            'index' => 'api.v1.notifications.index',
            'store' => 'api.v1.notifications.store',
            'show' => 'api.v1.notifications.show',
            'update' => 'api.v1.notifications.update',
            'destroy' => 'api.v1.notifications.destroy',
        ]);

        Route::post('notifications/{notification}/actions', [NotificationController::class, 'recordAction'])
            ->name('api.v1.notifications.actions');

        Route::get('history', [HistoryController::class, 'index'])->name('api.v1.history.index');
    });
});
