<?php

use App\Http\Controllers\api\main\ArrangementController;
use App\Http\Controllers\api\main\KontakController;
use App\Http\Controllers\api\main\KontakJenisController;
use App\Http\Controllers\api\main\UserActivityLogController;
use App\Http\Controllers\api\main\MenuBodyController;
use App\Http\Controllers\api\main\MenuCategoryController;
use App\Http\Controllers\api\main\MenuChildController;
use App\Http\Controllers\api\main\MenuModuleController;
use App\Http\Controllers\api\main\QuickAddController;
use App\Http\Controllers\api\main\RoleAccessController;
use App\Http\Controllers\api\main\RoleController;
use App\Http\Controllers\api\main\UserController;
use App\Http\Controllers\api\main\UserProfileController;
use Illuminate\Support\Facades\Route;

// begin:: role
Route::prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::post('/', [RoleController::class, 'store']);
    Route::get('/{id_role}', [RoleController::class, 'show']);
    Route::put('/{id_role}', [RoleController::class, 'update']);
    Route::delete('/{id_role}', [RoleController::class, 'destroy']);
    Route::get('/access/{id_role}/{id_menu_module}', [RoleController::class, 'access']);
});
// end:: role

// begin:: role access
Route::prefix('role-accesses')->group(function () {
    Route::get('/{id_role}', [RoleAccessController::class, 'index']);
    Route::post('/', [RoleAccessController::class, 'store']);
    Route::delete('/{id_role_access}', [RoleAccessController::class, 'destroy']);
    Route::post('/action/{id_role_access}', [RoleAccessController::class, 'action']);
    Route::get('/trees/{id_role}/{id_menu_module}', [RoleAccessController::class, 'trees']);
});
// end:: role access

// begin:: users
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
    Route::post('/active/{id}', [UserController::class, 'active']);
    Route::post('/reset/{id}', [UserController::class, 'reset']);
});

Route::prefix('user-profile')->group(function () {
    Route::get('/', [UserProfileController::class, 'index']);
    Route::post('/image', [UserProfileController::class, 'store_avatar']);
    Route::put('/', [UserProfileController::class, 'update_avatar']);
    Route::delete('/image', [UserProfileController::class, 'destroy_avatar']);
});
// end:: users

// begin:: arrangement
Route::prefix('arrangement')->group(function () {
    Route::get('/', [ArrangementController::class, 'index']);
    Route::post('/', [ArrangementController::class, 'store']);
    Route::get('/{key}', [ArrangementController::class, 'show']);
    Route::post('/image', [ArrangementController::class, 'store_image']);
    Route::delete('/image', [ArrangementController::class, 'delete_image']);
});
// end:: arrangement

// begin:: log_activity
Route::prefix('user_activity_logs')->group(function () {
    Route::get('/{type?}', [UserActivityLogController::class, 'index']);
});
// end:: log_activity

// begin:: menu module
Route::apiResource('/menu-modules', MenuModuleController::class);
// end:: menu module

// begin:: kontak
Route::apiResource('/kontak', KontakController::class);
// end:: kontak

// begin:: kontak jenis
Route::apiResource('/kontak-jenis', KontakJenisController::class);
// end:: kontak jenis

Route::prefix('menu')->group(function () {
    // begin:: menu category
    Route::prefix('categories')->group(function () {
        Route::get('/{id_menu_module}', [MenuCategoryController::class, 'index']);
        Route::post('/', [MenuCategoryController::class, 'store']);
        Route::get('/{id_menu_module}/{id_menu_category}', [MenuCategoryController::class, 'show']);
        Route::put('/{id_menu_module}/{id_menu_category}', [MenuCategoryController::class, 'update']);
        Route::delete('/{id_menu_module}/{id_menu_category}', [MenuCategoryController::class, 'destroy']);
    });
    // end:: menu category

    // begin:: menu body
    Route::prefix('bodies')->group(function () {
        Route::get('/{id_menu_category}', [MenuBodyController::class, 'index']);
        Route::post('/', [MenuBodyController::class, 'store']);
        Route::get('/{id_menu_category}/{id_menu_body}', [MenuBodyController::class, 'show']);
        Route::put('/{id_menu_category}/{id_menu_body}', [MenuBodyController::class, 'update']);
        Route::delete('/{id_menu_category}/{id_menu_body}', [MenuBodyController::class, 'destroy']);
        Route::post('/active/{id_menu_category}/{id_menu_body}', [MenuBodyController::class, 'active']);
    });
    // end:: menu body

    // begin:: menu child
    Route::prefix('child')->group(function () {
        Route::get('/{id_menu_body}', [MenuChildController::class, 'index']);
        Route::post('/', [MenuChildController::class, 'store']);
        Route::get('/{id_menu_body}/{id_menu_child}', [MenuChildController::class, 'show']);
        Route::put('/{id_menu_body}/{id_menu_child}', [MenuChildController::class, 'update']);
        Route::delete('/{id_menu_body}/{id_menu_child}', [MenuChildController::class, 'destroy']);
    });
    // end:: menu child
});

// begin:: quick add
Route::prefix('quick-add')->group(function () {
    Route::post('/kontak', [QuickAddController::class, 'kontak']);
});
// end:: quick add
