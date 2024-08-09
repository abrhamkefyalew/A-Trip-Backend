<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\DriverController;
use App\Http\Controllers\Api\V1\Admin\VehicleController;
use App\Http\Controllers\Api\V1\Admin\ContractController;
use App\Http\Controllers\Api\V1\Admin\SupplierController;
use App\Http\Controllers\Api\V1\Admin\VehicleNameController;
use App\Http\Controllers\Api\V1\Admin\VehicleTypeController;
use App\Http\Controllers\Api\V1\Admin\OrganizationController;
use App\Http\Controllers\Api\V1\Admin\ContractDetailController;
use App\Http\Controllers\Api\V1\Admin\OrganizationUserController;
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


            Route::prefix('vehicle_types')->group(function () {
                Route::post('/', [VehicleTypeController::class, 'store']);
                Route::get('/', [VehicleTypeController::class, 'index']);
                Route::prefix('/{vehicleType}')->group(function () {
                    Route::get('/', [VehicleTypeController::class, 'show']);
                    Route::put('/', [VehicleTypeController::class, 'update']);
                    Route::delete('/', [VehicleTypeController::class, 'destroy']);
                }); 
            });


            Route::prefix('vehicle_names')->group(function () {
                Route::post('/', [VehicleNameController::class, 'store']);
                Route::get('/', [VehicleNameController::class, 'index']);
                Route::prefix('/{vehicleName}')->group(function () {
                    Route::get('/', [VehicleNameController::class, 'show']);
                    Route::put('/', [VehicleNameController::class, 'update']);
                    Route::delete('/', [VehicleNameController::class, 'destroy']);
                }); 
            });


            Route::prefix('vehicles')->group(function () {
                Route::post('/', [VehicleController::class, 'store']);
                Route::get('/', [VehicleController::class, 'index']);
                Route::prefix('/{vehicle}')->group(function () {
                    Route::get('/', [VehicleController::class, 'show']);
                    Route::put('/', [VehicleController::class, 'update']);
                    Route::delete('/', [VehicleController::class, 'destroy']);
                }); 
            });


            Route::prefix('organizations')->group(function () {
                Route::post('/', [OrganizationController::class, 'store']);
                Route::get('/', [OrganizationController::class, 'index']);
                Route::prefix('/{organization}')->group(function () {
                    Route::get('/', [OrganizationController::class, 'show']);
                    Route::put('/', [OrganizationController::class, 'update']);
                    Route::delete('/', [OrganizationController::class, 'destroy']);
                }); 
            });


            Route::prefix('organization_users')->group(function () {
                Route::post('/', [OrganizationUserController::class, 'store']);
                Route::get('/', [OrganizationUserController::class, 'index']);
                Route::prefix('/{organizationUser}')->group(function () {
                    Route::get('/', [OrganizationUserController::class, 'show']);
                    Route::put('/', [OrganizationUserController::class, 'update']);
                    Route::delete('/', [OrganizationUserController::class, 'destroy']);
                }); 
            });


            Route::prefix('organization_users')->group(function () {
                Route::post('/', [ContractController::class, 'store']);
                Route::get('/', [ContractController::class, 'index']);
                Route::prefix('/{organizationUser}')->group(function () {
                    Route::get('/', [ContractController::class, 'show']);
                    Route::put('/', [ContractController::class, 'update']);
                    Route::delete('/', [ContractController::class, 'destroy']);
                }); 
            });


            Route::prefix('organization_users')->group(function () {
                Route::post('/', [ContractDetailController::class, 'store']);
                Route::get('/', [ContractDetailController::class, 'index']);
                Route::prefix('/{organizationUser}')->group(function () {
                    Route::get('/', [ContractDetailController::class, 'show']);
                    Route::put('/', [ContractDetailController::class, 'update']);
                    Route::delete('/', [ContractDetailController::class, 'destroy']);
                }); 
            });





        });

    });








});