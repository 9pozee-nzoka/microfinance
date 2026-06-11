<?php
// app/Http/Middleware/EnsureSingleSession.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user) {
            // Check account is active
            if ($user->status !== 'active') {
                auth()->logout();
                $request->session()->invalidate();
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Your account has been ' . $user->status . '. Please contact your administrator.'], 403);
                }
                return redirect('/login')->withErrors([
                    'email' => 'Your account has been ' . $user->status . '. Please contact your administrator.',
                ]);
            }

            // Check session is still valid (single-session enforcement)
            if ($user->session_id && $user->session_id !== session()->getId()) {
                auth()->logout();
                $request->session()->invalidate();
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Your session was terminated because you logged in from another device.'], 401);
                }
                return redirect('/login')->withErrors([
                    'email' => 'Your session was terminated because you logged in from another device.',
                ]);
            }
        }

        return $next($request);
    }
}
