<?php

use App\Http\Middleware\AuthenticateWhenEnabled;
use App\Http\Middleware\EnsureApplicationInstalled;
use App\Http\Middleware\EnsureApplicationNotInstalled;
use App\Http\Middleware\EnsureAuthenticationEnabled;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'application.installed' => EnsureApplicationInstalled::class,
            'application.pending' => EnsureApplicationNotInstalled::class,
            'auth.when-enabled' => AuthenticateWhenEnabled::class,
            'auth.enabled' => EnsureAuthenticationEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
