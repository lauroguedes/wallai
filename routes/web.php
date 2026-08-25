<?php

use App\Http\Controllers\ReadinessController;
use Illuminate\Support\Facades\Route;

Route::get('/ready', ReadinessController::class)->name('ready');

Route::livewire('/setup', 'pages::setup')
    ->middleware('application.pending')
    ->name('setup');

Route::middleware('application.installed')->group(function (): void {
    Route::livewire('/', 'pages::home')
        ->middleware('auth.when-enabled')
        ->name('home');

    Route::middleware(['auth.enabled', 'guest'])->group(function (): void {
        Route::livewire('/login', 'pages::login')->name('login');
        Route::livewire('/invitation/{token}', 'pages::accept-invitation')->name('invitation.accept');
    });
});
