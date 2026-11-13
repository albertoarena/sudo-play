<?php

declare(strict_types=1);

use App\Native\BootstrapCache;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Before the app is built, so the launch caches stay out of the signed bundle.
BootstrapCache::keepOutsideTheBundle(dirname(__DIR__), getenv('NATIVEPHP_USER_DATA_PATH'));

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
