<?php

namespace App\Http\Middleware;

use App\Services\ApplicationSetup;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWhenEnabled
{
    public function __construct(private ApplicationSetup $setup) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->setup->authenticationEnabled() && Auth::guest()) {
            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
