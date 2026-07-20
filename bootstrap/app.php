<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'customer.portal' => \App\Http\Middleware\CustomerPortalMiddleware::class,
            'role'            => \App\Http\Middleware\RoleMiddleware::class,
            'staff'           => \App\Http\Middleware\StaffMiddleware::class,
        ]);

        // Exclude M-Pesa Safaricom callback URLs from CSRF
        $middleware->validateCsrfTokens(except: [
            'payment/stk/callback',
            'payment/b2c/result',
            'payment/b2c/timeout',
            'payment/c2b/validation',
            'payment/c2b/confirmation',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
