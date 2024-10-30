<?php

namespace App\Providers;

use App\Services\AbstractImageGenerator;
use App\Services\Flux;
use App\Services\Unsplash;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AbstractImageGenerator::class, function ($app) {
            return match (config('app.image_generator_service')) {
                'unsplash' => $app->make(Unsplash::class),
                'flux' => $app->make(Flux::class),
                default => throw new \Exception('Invalid image generator service'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
