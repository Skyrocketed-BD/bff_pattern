<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\PushyController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'api',
    'prefix'     => 'auth',
], function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::controller(AuthController::class)->middleware('jwtChecking')->group(function () {
        Route::post('logout', 'logout');
        Route::post('refresh', 'refresh');
        Route::post('me', 'me');
    });

    Route::post('/mobile-login', [AuthController::class, 'mobile_login'])->name('mobile_login');
});

Route::group([
    'middleware' => ['jwtChecking', 'jsonApiData', 'throttle:60,1'],
], function () {
    // === BEGIN:: MAIN ===
    require __DIR__ . '/api/main.php';
    // === END:: MAIN ===

    // === BEGIN:: FINANCE ===
    require __DIR__ . '/api/finance.php';
    // === END:: FINANCE ===

    // === BEGIN:: OPERATION ===
    require __DIR__ . '/api/operation.php';
    // === END:: OPERATION ===

    // === BEGIN:: CONTRACT & LEGAL ===
    require __DIR__ . '/api/contract_legal.php';
    // === END:: CONTRACT & LEGAL ===
});

Route::group([
    'middleware' => 'jwtChecking',
], function () {
    Route::post('/push-token', [PushyController::class, 'store']);
});

