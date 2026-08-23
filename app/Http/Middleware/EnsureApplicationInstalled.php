<?php

namespace App\Http\Middleware;

use App\Services\ApplicationSetup;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationInstalled
{
    public function __construct(private ApplicationSetup $setup) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->setup->isInstalled()) {
            return redirect()->route('setup');
        }

        return $next($request);
    }
}
