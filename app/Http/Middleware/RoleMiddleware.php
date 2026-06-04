<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Super admin can access everything
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Check if user has any of the required roles
        if (!$user->hasAnyRole($roles)) {
            // Log unauthorized access attempt
            \Illuminate\Support\Facades\Log::warning('Unauthorized access attempt', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_roles' => $user->roles->pluck('name')->toArray(),
                'required_roles' => $roles,
                'url' => $request->url(),
                'ip' => $request->ip(),
            ]);

            // Redirect based on user role
            if ($user->hasRole('customer')) {
                return redirect()->route('portal.dashboard')
                    ->with('error', 'You do not have access to the staff portal.');
            }

            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
