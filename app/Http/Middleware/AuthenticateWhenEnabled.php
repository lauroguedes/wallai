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
        if ($this->setup->authenticationEnabled()) {
            if (Auth::guest()) {
                return redirect()->guest(route('login'));
            }

            if (! Auth::user()->is_active) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', 'Your account has been deactivated.');
            }
        }

        return $next($request);
    }
}
