<?php

namespace App\Http\Middleware;

use App\Services\ApplicationSetup;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticationEnabled
{
    public function __construct(private ApplicationSetup $setup) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->setup->isInstalled()) {
            return redirect()->route('setup');
        }

        if (! $this->setup->authenticationEnabled()) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
