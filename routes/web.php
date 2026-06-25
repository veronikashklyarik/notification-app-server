<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\EmailVerificationNoticeController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResendVerificationEmailController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HistoryController;
use App\Livewire\EventList;
use App\Livewire\EventShow;
use App\Livewire\Home;
use App\Livewire\NotificationCreate;
use App\Livewire\NotificationEdit;
use App\Livewire\NotificationList;
use App\Livewire\NotificationShow;
use App\Livewire\Profile;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('home'));
Route::get('offline', fn () => view('pwa.offline'))->name('offline');
Route::middleware(['auth', 'verified'])->get('install', fn () => view('install'))->name('install');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);

    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [LogoutController::class, 'destroy'])->name('logout');

    Route::get('email/verify', EmailVerificationNoticeController::class)->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', EmailVerificationController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/verification-notification', ResendVerificationEmailController::class)
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('home', Home::class)->name('home');

    Route::get('profile', Profile::class)->name('profile.edit');

    Route::get('notifications', NotificationList::class)->name('notifications.index');
    Route::get('notifications/create', NotificationCreate::class)->name('notifications.create');
    Route::get('notifications/{notification}', NotificationShow::class)->name('notifications.show');
    Route::get('notifications/{notification}/edit', NotificationEdit::class)->name('notifications.edit');

    Route::get('history', [HistoryController::class, 'index'])->name('history.index');

    Route::get('events', EventList::class)->name('events.index');
    Route::get('events/{event}', EventShow::class)->name('events.show');
});
