<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffMiddleware
{
    /**
     * Ensure the user is a staff member (not a customer).
     * All staff routes should use this middleware.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Block customers from staff routes
        if ($user->hasRole('customer')) {
            auth()->logout();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Customers cannot access staff routes.'], 403);
            }
            return redirect()->route('portal.login')
                ->withErrors(['email' => 'Please use the customer portal to login.']);
        }

        return $next($request);
    }
}
