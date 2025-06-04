<?php

use App\Http\Middleware\CheckTokenAbilities;
use App\Http\Middleware\LanguageMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use PragmaRX\Google2FALaravel\Middleware as Google2FALaravelMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'check.abilities' => CheckTokenAbilities::class,
            '2fa' => Google2FALaravelMiddleware::class,
            'blog.validate.params' => \App\Http\Middleware\Frontend\BlogParametersValidation::class,
        ]);
        $middleware->web(
            append: [
                LanguageMiddleware::class,

            ]
        );

        $middleware->api(
            prepend: [
                EnsureFrontendRequestsAreStateful::class,
            ]
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
