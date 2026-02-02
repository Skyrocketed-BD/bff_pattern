<?php

use App\Http\Controllers\Bff\Web\AuthController;
use App\Http\Controllers\Bff\Web\Finance\CoaController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('login', 'login');
    Route::post('logout', 'logout');
    Route::post('verify', 'verify');
    Route::get('me', 'me');
});

Route::group([
    'middleware' => ['VerifyBffSession'],
], function () {
    Route::controller(CoaController::class)->prefix('coas')->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
    });
});
