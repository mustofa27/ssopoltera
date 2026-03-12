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
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'ip.policy' => \App\Http\Middleware\EnforceIpPolicy::class,
            'idle.timeout' => \App\Http\Middleware\EnsureSessionNotIdle::class,
            'password.expiry' => \App\Http\Middleware\EnsurePasswordNotExpired::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
