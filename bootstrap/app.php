<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['api'])->group(__DIR__ . '/../routes/api/main.php');
            Route::middleware(['api'])->group(__DIR__ . '/../routes/api/finance.php');
            Route::middleware(['api'])->group(__DIR__ . '/../routes/api/operation.php');
        }
    )
    ->withMiddleware(new App\Http\AppMiddleware())
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 401);
            }
        });
    })->create();
