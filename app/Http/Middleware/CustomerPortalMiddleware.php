<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerPortalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('portal.login');
        }

        if (! auth()->user()->hasRole('customer')) {
            auth()->logout();
            return redirect()->route('portal.login')
                ->withErrors(['email' => 'This portal is for customers only.']);
        }

        return $next($request);
    }
}
