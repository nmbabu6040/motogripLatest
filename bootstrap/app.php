<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The payment gateway posts back to these two addresses without a session; they trust nothing and ask the gateway to confirm.
        $middleware->validateCsrfTokens(except: ['pay-callback/*', 'pay-ipn']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
