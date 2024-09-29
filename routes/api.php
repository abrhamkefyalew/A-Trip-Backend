<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\BankController;
use App\Http\Controllers\Api\V1\Admin\TripController;
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Http\Controllers\Api\V1\Admin\DriverController;
use App\Http\Controllers\Api\V1\Admin\InvoiceController;
use App\Http\Controllers\Api\V1\Admin\VehicleController;
use App\Http\Controllers\Api\V1\Admin\ContractController;
use App\Http\Controllers\Api\V1\Admin\SupplierController;
use App\Http\Controllers\Api\V1\Admin\DashBoardController;
use App\Http\Controllers\Api\V1\Admin\VehicleNameController;
use App\Http\Controllers\Api\V1\Admin\VehicleTypeController;
use App\Http\Controllers\Api\V1\Admin\OrganizationController;
use App\Http\Controllers\Api\V1\Admin\ContractDetailController;
use App\Http\Controllers\Api\V1\Admin\OrganizationUserController;
use App\Http\Controllers\Api\V1\Auth\AdminAuth\AdminAuthController;
use App\Http\Controllers\Api\V1\Auth\DriverAuth\DriverAuthController;
use App\Http\Controllers\Api\V1\Auth\CustomerAuth\CustomerAuthController;
use App\Http\Controllers\Api\V1\Auth\SupplierAuth\SupplierAuthController;
use App\Http\Controllers\Api\V1\Driver\TripController as TripForDriverController;
use App\Http\Controllers\Api\V1\Driver\OrderController as OrderForDriverController;
use App\Http\Controllers\Api\V1\Driver\VehicleController as VehicleForDriverController;
use App\Http\Controllers\Api\V1\Supplier\OrderController as OrderForSupplierController;
use App\Http\Controllers\Api\V1\Auth\OrganizationUserAuth\OrganizationUserAuthController;
use App\Http\Controllers\Api\V1\Supplier\VehicleController as VehicleForSupplierController;
use App\Http\Controllers\Api\V1\OrganizationUser\TripController as TripForOrganizationController;
use App\Http\Controllers\Api\V1\Customer\VehicleNameController as VehicleNameForCustomerController;
use App\Http\Controllers\Api\V1\Customer\VehicleTypeController as VehicleTypeForCustomerController;
use App\Http\Controllers\Api\V1\OrganizationUser\OrderController as OrderForOrganizationController;
use App\Http\Controllers\Api\V1\Supplier\VehicleNameController as VehicleNameForSupplierController;
use App\Http\Controllers\Api\V1\Supplier\VehicleTypeController as VehicleTypeForSupplierController;
use App\Http\Controllers\Api\V1\OrganizationUser\InvoiceController as InvoiceForOrganizationController;
use App\Http\Controllers\Api\V1\OrganizationUser\ContractDetailController as ContractDetailForOrganizationController;

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


            Route::prefix('banks')->group(function () {
                Route::post('/', [BankController::class, 'store']);
                Route::get('/', [BankController::class, 'index']);
                Route::prefix('/{bank}')->group(function () {
                    Route::get('/', [BankController::class, 'show']);
                    Route::put('/', [BankController::class, 'update']);
                    Route::delete('/', [BankController::class, 'destroy']);
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
                Route::get('/search_by_vehicle_type', [VehicleNameController::class, 'searchByVehicleType']);
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


            Route::prefix('contracts')->group(function () {
                Route::post('/', [ContractController::class, 'store']);
                Route::get('/', [ContractController::class, 'index']);
                Route::prefix('/{contract}')->group(function () {
                    Route::get('/', [ContractController::class, 'show']);
                    Route::put('/', [ContractController::class, 'update']);
                    Route::delete('/', [ContractController::class, 'destroy']);
                }); 
            });


            Route::prefix('contract_details')->group(function () {
                Route::post('/', [ContractDetailController::class, 'store']);
                Route::get('/', [ContractDetailController::class, 'index']);
                Route::prefix('/{contractDetail}')->group(function () {
                    Route::get('/', [ContractDetailController::class, 'show']);
                    Route::put('/', [ContractDetailController::class, 'update']);
                    Route::delete('/', [ContractDetailController::class, 'destroy']);
                }); 
            });


            Route::prefix('orders')->group(function () {
                Route::post('/', [OrderController::class, 'store']);
                Route::get('/', [OrderController::class, 'index']);
                Route::prefix('/{order}')->group(function () {
                    Route::get('/', [OrderController::class, 'show']);
                    Route::put('/', [OrderController::class, 'update']);
                    Route::put('/accept_order', [OrderController::class, 'acceptOrder']);
                    Route::put('/start_order', [OrderController::class, 'startOrder']);
                    Route::put('/complete_order', [OrderController::class, 'completeOrder']);
                    Route::delete('/', [OrderController::class, 'destroy']);
                }); 
            });



            Route::prefix('invoices')->group(function () {
                Route::post('/', [InvoiceController::class, 'store']);
                Route::get('/', [InvoiceController::class, 'index']);
                Route::get('/index_by_invoice_code', [InvoiceController::class, 'indexByInvoiceCode']);
                Route::prefix('/{invoice}')->group(function () {
                    Route::get('/', [InvoiceController::class, 'show']);
                    Route::put('/', [InvoiceController::class, 'update']);
                    Route::delete('/', [InvoiceController::class, 'destroy']);
                }); 
            });


            Route::prefix('trips')->group(function () {
                Route::post('/', [TripController::class, 'store']);
                Route::get('/', [TripController::class, 'index']);
                Route::prefix('/{trip}')->group(function () {
                    Route::get('/', [TripController::class, 'show']);
                    Route::put('/', [TripController::class, 'update']);
                    Route::put('/approve_trip', [TripController::class, 'approveTrip']);
                    Route::delete('/', [TripController::class, 'destroy']);
                }); 
            });



            Route::prefix('dash_board')->group(function () {
                Route::get('/dash_board_count_one', [DashBoardController::class, 'DashBoardCountOne']);
            });





        });

    });




    // organization users route (for organizations)
    Route::prefix('organization_user')->group(function () {
        Route::prefix('')->group(function () {
            // there should NOT be OrganizationUser registration, -  
            // OrganizationUser should be stored by an already existing OrganizationUser admin or super admin of the system -
            // there should be a route for OrganizationUser storing by both OrganizationUser admin and super admin
            Route::post('/login', [OrganizationUserAuthController::class, 'login']);

        });


        Route::prefix('call_backs')->group(function () {
            
            Route::prefix('invoices')->group(function () {
                Route::prefix('tele_birr')->group(function () {
                    Route::post('/pay_invoices_call_back_tele_birr', [InvoiceForOrganizationController::class, 'payInvoicesCallBackTelebirr']);
                });
            }); 

        });


        Route::middleware(['auth:sanctum', 'abilities:access-organizationUser'])->group(function () {

            Route::prefix('')->group(function () {
                Route::post('/logout', [OrganizationUserAuthController::class, 'logout']);
                Route::post('/logout-all-devices', [OrganizationUserAuthController::class, 'logoutAllDevices']);
            });

            Route::prefix('contract_details')->group(function () {
                Route::post('/', [ContractDetailForOrganizationController::class, 'store']);
                Route::get('/', [ContractDetailForOrganizationController::class, 'index']);
                Route::prefix('/{contractDetail}')->group(function () {
                    Route::get('/', [ContractDetailForOrganizationController::class, 'show']);
                    Route::put('/', [ContractDetailForOrganizationController::class, 'update']);
                    Route::delete('/', [ContractDetailForOrganizationController::class, 'destroy']);
                }); 
            });


            Route::prefix('orders')->group(function () {
                Route::post('/', [OrderForOrganizationController::class, 'store']);
                Route::get('/', [OrderForOrganizationController::class, 'index']);
                Route::prefix('/{order}')->group(function () {
                    Route::get('/', [OrderForOrganizationController::class, 'show']);
                    Route::put('/', [OrderForOrganizationController::class, 'update']);
                    Route::delete('/', [OrderForOrganizationController::class, 'destroy']);
                }); 
            });


            Route::prefix('invoices')->group(function () {
                Route::post('/', [InvoiceForOrganizationController::class, 'store']);
                Route::get('/', [InvoiceForOrganizationController::class, 'index']);
                Route::get('/index_by_invoice_code', [InvoiceForOrganizationController::class, 'indexByInvoiceCode']);
                Route::post('/pay_invoices', [InvoiceForOrganizationController::class, 'payInvoices']);
                Route::prefix('/{invoice}')->group(function () {
                    Route::get('/', [InvoiceForOrganizationController::class, 'show']);
                    Route::put('/', [InvoiceForOrganizationController::class, 'update']);
                    Route::delete('/', [InvoiceForOrganizationController::class, 'destroy']);
                }); 
            });


            Route::prefix('trips')->group(function () {
                Route::post('/', [TripForOrganizationController::class, 'store']);
                Route::get('/', [TripForOrganizationController::class, 'index']);
                Route::prefix('/{trip}')->group(function () {
                    Route::get('/', [TripForOrganizationController::class, 'show']);
                    Route::put('/', [TripForOrganizationController::class, 'update']);
                    Route::put('/approve_trip', [TripForOrganizationController::class, 'approveTrip']);
                    Route::delete('/', [TripForOrganizationController::class, 'destroy']);
                }); 
            });



        });

    });










    // Suppliers route (for Vehicle Suppliers)
    Route::prefix('supplier')->group(function () {
        Route::prefix('')->group(function () {
            // there should NOT be Supplier registration, -  
            // Supplier should be stored by super admin of the system -
            // there should be a route for Supplier storing by super admin
            Route::post('/login', [SupplierAuthController::class, 'login']);

        });


        Route::middleware(['auth:sanctum', 'abilities:access-supplier'])->group(function () {

            Route::prefix('')->group(function () {
                Route::post('/logout', [SupplierAuthController::class, 'logout']);
                Route::post('/logout-all-devices', [SupplierAuthController::class, 'logoutAllDevices']);
            });



            Route::prefix('orders')->group(function () {
                Route::post('/', [OrderForSupplierController::class, 'store']);
                Route::get('/', [OrderForSupplierController::class, 'index']);
                Route::get('/index_pending', [OrderForSupplierController::class, 'indexPending']);
                Route::prefix('/{order}')->group(function () {
                    Route::get('/', [OrderForSupplierController::class, 'show']);
                    Route::put('/accept_order', [OrderForSupplierController::class, 'acceptOrder']);
                    Route::put('/start_order', [OrderForSupplierController::class, 'startOrder']);
                    Route::put('/complete_order', [OrderForSupplierController::class, 'completeOrder']);
                    Route::delete('/', [OrderForSupplierController::class, 'destroy']);
                }); 
            });


            Route::prefix('vehicle_types')->group(function () {
                Route::post('/', [VehicleTypeForSupplierController::class, 'store']);
                Route::get('/', [VehicleTypeForSupplierController::class, 'index']);
                Route::prefix('/{vehicleType}')->group(function () {
                    Route::get('/', [VehicleTypeForSupplierController::class, 'show']);
                    Route::put('/', [VehicleTypeForSupplierController::class, 'update']);
                    Route::delete('/', [VehicleTypeForSupplierController::class, 'destroy']);
                }); 
            });


            Route::prefix('vehicle_names')->group(function () {
                Route::post('/', [VehicleNameForSupplierController::class, 'store']);
                Route::get('/', [VehicleNameForSupplierController::class, 'index']);
                Route::get('/search_by_vehicle_type', [VehicleNameForSupplierController::class, 'searchByVehicleType']);
                Route::prefix('/{vehicleName}')->group(function () {
                    Route::get('/', [VehicleNameForSupplierController::class, 'show']);
                    Route::put('/', [VehicleNameForSupplierController::class, 'update']);
                    Route::delete('/', [VehicleNameForSupplierController::class, 'destroy']);
                }); 
            });



            Route::prefix('vehicles')->group(function () {
                Route::post('/', [VehicleForSupplierController::class, 'store']);
                Route::get('/', [VehicleForSupplierController::class, 'index']);
                Route::prefix('/{vehicle}')->group(function () {
                    Route::get('/', [VehicleForSupplierController::class, 'show']);
                    Route::put('/', [VehicleForSupplierController::class, 'update']);
                    Route::delete('/', [VehicleForSupplierController::class, 'destroy']);
                }); 
            });

            




        });

    });











    // Drivers route (for Vehicle Drivers)
    Route::prefix('driver')->group(function () {
        Route::prefix('')->group(function () {
            // there should NOT be Drivers registration, -  
            // Drivers should be stored by super admin of the system -
            // there should be a route for Drivers storing by super admin
            Route::post('/login', [DriverAuthController::class, 'login']);

        });


        Route::middleware(['auth:sanctum', 'abilities:access-driver'])->group(function () {

            Route::prefix('')->group(function () {
                Route::post('/logout', [DriverAuthController::class, 'logout']);
                Route::post('/logout-all-devices', [DriverAuthController::class, 'logoutAllDevices']);
            });



            Route::prefix('orders')->group(function () {
                Route::post('/', [OrderForDriverController::class, 'store']);
                Route::get('/', [OrderForDriverController::class, 'index']);
                Route::get('/index_pending', [OrderForDriverController::class, 'indexPending']);
                Route::prefix('/{order}')->group(function () {
                    Route::get('/', [OrderForDriverController::class, 'show']);
                    Route::put('/accept_order', [OrderForDriverController::class, 'acceptOrder']);
                    Route::put('/start_order', [OrderForDriverController::class, 'startOrder']);
                    Route::put('/complete_order', [OrderForDriverController::class, 'completeOrder']);
                    Route::delete('/', [OrderForDriverController::class, 'destroy']);
                }); 
            });



            Route::prefix('vehicles')->group(function () {
                Route::post('/', [VehicleForDriverController::class, 'store']);
                Route::get('/', [VehicleForDriverController::class, 'index']);
                Route::prefix('/{vehicle}')->group(function () {
                    Route::get('/', [VehicleForDriverController::class, 'show']);
                    Route::put('/', [VehicleForDriverController::class, 'update']);
                    Route::delete('/', [VehicleForDriverController::class, 'destroy']);
                }); 
            });


            Route::prefix('trips')->group(function () {
                Route::post('/', [TripForDriverController::class, 'store']);
                Route::get('/', [TripForDriverController::class, 'index']);
                Route::prefix('/{trip}')->group(function () {
                    Route::get('/', [TripForDriverController::class, 'show']);
                    Route::put('/', [TripForDriverController::class, 'update']);
                    Route::delete('/', [TripForDriverController::class, 'destroy']);
                }); 
            });

            




        });

    });










    // Customers route (for individual Customers)
    Route::prefix('customer')->group(function () {
        Route::prefix('')->group(function () {
            // there should be BOTH Customer registration and Customer Store, -  
            //
            // Customer can be registered by himself on the system -
            // or
            // Customer can be stored by super admin of the system -
            //
            // there should be a route for Customer Registration by himself
            // there should be a route for Customer storing by super admin

            Route::post('/register', [CustomerAuthController::class, 'register']);
            Route::post('/login', [CustomerAuthController::class, 'login']);

        });


        Route::middleware(['auth:sanctum', 'abilities:access-customer'])->group(function () {

            Route::prefix('')->group(function () {
                Route::post('/logout', [CustomerAuthController::class, 'logout']);
                Route::post('/logout-all-devices', [CustomerAuthController::class, 'logoutAllDevices']);
            });


            Route::prefix('vehicle_types')->group(function () {
                Route::post('/', [VehicleTypeForCustomerController::class, 'store']);
                Route::get('/', [VehicleTypeForCustomerController::class, 'index']);
                Route::prefix('/{vehicleType}')->group(function () {
                    Route::get('/', [VehicleTypeForCustomerController::class, 'show']);
                    Route::put('/', [VehicleTypeForCustomerController::class, 'update']);
                    Route::delete('/', [VehicleTypeForCustomerController::class, 'destroy']);
                }); 
            });


            Route::prefix('vehicle_names')->group(function () {
                Route::post('/', [VehicleNameForCustomerController::class, 'store']);
                Route::get('/', [VehicleNameForCustomerController::class, 'index']);
                Route::get('/search_by_vehicle_type', [VehicleNameForCustomerController::class, 'searchByVehicleType']);
                Route::prefix('/{vehicleName}')->group(function () {
                    Route::get('/', [VehicleNameForCustomerController::class, 'show']);
                    Route::put('/', [VehicleNameForCustomerController::class, 'update']);
                    Route::delete('/', [VehicleNameForCustomerController::class, 'destroy']);
                }); 
            });


            



        });

    });



        




});