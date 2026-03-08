<?php

namespace App\Providers;

use App\Livewire\Hooks\HandleExceptions;
use App\Services\WallpaperService;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WallpaperService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::componentHook(HandleExceptions::class);
    }
}
