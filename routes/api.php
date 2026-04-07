<?php

use App\Http\Controllers\Api\V1\Auth\ChangePasswordController;
use App\Http\Controllers\Api\V1\Auth\DeleteAccountController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RefreshTokenController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\VersionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Version check (public endpoint)
    Route::get('version/check', [VersionController::class, 'check'])->name('api.v1.version.check');

    Route::prefix('auth')->middleware('throttle:api-auth')->group(function (): void {
        Route::post('register', [RegisterController::class, 'store'])->name('api.v1.auth.register');
        Route::post('login', [LoginController::class, 'store'])->name('api.v1.auth.login');
        Route::post('refresh', [RefreshTokenController::class, 'store'])
            ->middleware('auth:sanctum')
            ->name('api.v1.auth.refresh');
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::prefix('auth')->group(function (): void {
            Route::delete('logout', [LogoutController::class, 'destroy'])->name('api.v1.auth.logout');
            Route::put('password', [ChangePasswordController::class, 'update'])->name('api.v1.auth.password');
            Route::delete('account', [DeleteAccountController::class, 'destroy'])->name('api.v1.auth.account.destroy');
        });

        Route::get('profile', [ProfileController::class, 'show'])->name('api.v1.profile');
        Route::put('profile', [ProfileController::class, 'update'])->name('api.v1.profile.update');
        Route::post('profile/avatar', [ProfileController::class, 'updateAvatar'])->name('api.v1.profile.avatar');

        Route::post('device-tokens', [DeviceTokenController::class, 'store'])->name('api.v1.device-tokens.store');
        Route::delete('device-tokens', [DeviceTokenController::class, 'destroy'])->name('api.v1.device-tokens.destroy');

        Route::apiResource('notifications', NotificationController::class)->names([
            'index' => 'api.v1.notifications.index',
            'store' => 'api.v1.notifications.store',
            'show' => 'api.v1.notifications.show',
            'update' => 'api.v1.notifications.update',
            'destroy' => 'api.v1.notifications.destroy',
        ]);

        Route::get('notifications/{notification}/events', [NotificationController::class, 'events'])
            ->name('api.v1.notifications.events');

        Route::get('events', [EventController::class, 'index'])->name('api.v1.events.index');
        Route::patch('events/{event}', [EventController::class, 'update'])->name('api.v1.events.update');
    });
});
