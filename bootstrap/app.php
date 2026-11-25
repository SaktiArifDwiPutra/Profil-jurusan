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
->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            // Pastikan ejaannya benar:
           // 'prevent-back-history' => \App\Http\Middleware\PreventBackHistory::class,
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class, // (Opsional, tapi bagus ada)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();