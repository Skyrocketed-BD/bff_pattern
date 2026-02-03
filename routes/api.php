<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Finance\AssetCategoryController;
use App\Http\Controllers\Api\Finance\CoaController;
use App\Http\Controllers\Api\Finance\CoaGroupController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('login', 'login');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::post('me', 'me');
});

Route::group([
    'middleware' => ['jwtChecking', 'jsonApiData', 'throttle:60,1'],
], function () {
    Route::controller(CoaController::class)->prefix('coas')->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
    });

    Route::prefix('asset')->group(function () {
        Route::controller(AssetCategoryController::class)->prefix('category')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{id}', 'show');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });
    });

    Route::prefix('coa')->group(function () {
        Route::controller(CoaGroupController::class)->prefix('groups')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{id}', 'show');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });
    });
});
