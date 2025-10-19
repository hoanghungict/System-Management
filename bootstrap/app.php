<?php

use App\Http\Middleware\JwtMiddleware;
use App\Http\Middleware\AdminOnlyMiddleware;
use App\Http\Middleware\LecturerOnlyMiddleware;
use App\Http\Middleware\StudentOnlyMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',

        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API middleware group
        $middleware->group('api', [
            \App\Http\Middleware\ForceUtf8Middleware::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Web middleware group
        $middleware->group('web', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Alias middleware
        $middleware->alias([
            'jwt' => JwtMiddleware::class,
            'admin' => AdminOnlyMiddleware::class,
            'lecturer' => LecturerOnlyMiddleware::class,
            'student' => StudentOnlyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();