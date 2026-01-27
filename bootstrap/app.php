<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use App\Http\Middleware\APIKeyMiddleware;
use App\Http\Middleware\LocalizationMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Tvoje doplnenie do web group (mal si to v starom Kernel.php)
        $middleware->web(append: [
            LocalizationMiddleware::class,
        ]);

        // Route middleware aliasy (používaš v routes/api.php a prípadne aj inde)
        $middleware->alias([
            'api_key'      => APIKeyMiddleware::class,
            'localization' => LocalizationMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
