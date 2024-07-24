<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\DriverController;
use App\Http\Controllers\Api\V1\Admin\SupplierController;
use App\Http\Controllers\Api\V1\Auth\AdminAuth\AdminAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});




//
Route::prefix('v1')->group(function () {

    // open routes


    
    // admin routes
    Route::prefix('admin')->group(function () {
        Route::prefix('')->group(function () {
            // there should NOT be admin registration, -  
            // admin should be seeded or stored by an already existing admin -
            // there is a route for admin storing
            Route::post('/login', [AdminAuthController::class, 'login']);

        });


        Route::middleware(['auth:sanctum', 'abilities:access-admin'])->group(function () {

            Route::prefix('')->group(function () {
                Route::post('/logout', [AdminAuthController::class, 'logout']);
                Route::post('/logout-all-devices', [AdminAuthController::class, 'logoutAllDevices']);
            });


            Route::prefix('admins')->group(function () {
                Route::post('/', [AdminController::class, 'store']);
                Route::get('/', [AdminController::class, 'index']);
                Route::prefix('/{admin}')->group(function () {
                    Route::get('/', [AdminController::class, 'show']);
                    Route::put('/', [AdminController::class, 'update']);
                    Route::delete('/', [AdminController::class, 'destroy']);
                }); 
            });

            

            Route::prefix('suppliers')->group(function () {
                Route::post('/', [SupplierController::class, 'store']);
                Route::get('/', [SupplierController::class, 'index']);
                Route::prefix('/{supplier}')->group(function () {
                    Route::get('/', [SupplierController::class, 'show']);
                    Route::put('/', [SupplierController::class, 'update']);
                    Route::delete('/', [SupplierController::class, 'destroy']);
                }); 
            });


            Route::prefix('drivers')->group(function () {
                Route::post('/', [DriverController::class, 'store']);
                Route::get('/', [DriverController::class, 'index']);
                Route::prefix('/{driver}')->group(function () {
                    Route::get('/', [DriverController::class, 'show']);
                    Route::put('/', [DriverController::class, 'update']);
                    Route::delete('/', [DriverController::class, 'destroy']);
                }); 
            });







        });

    });








});