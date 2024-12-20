<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\BidController;
use App\Http\Controllers\Api\V1\Admin\BankController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\TripController;
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Http\Controllers\Api\V1\Admin\DriverController;
use App\Http\Controllers\Api\V1\Admin\InvoiceController;
use App\Http\Controllers\Api\V1\Admin\VehicleController;
use App\Http\Controllers\Api\V1\Admin\ConstantController;
use App\Http\Controllers\Api\V1\Admin\ContractController;
use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\SupplierController;
use App\Http\Controllers\Api\V1\Admin\DashBoardController;
use App\Http\Controllers\Api\V1\Admin\OrderUserController;
use App\Http\Controllers\Api\V1\Admin\PermissionController;
use App\Http\Controllers\Api\V1\Admin\InvoiceTripController;
use App\Http\Controllers\Api\V1\Admin\InvoiceUserController;
use App\Http\Controllers\Api\V1\Admin\VehicleNameController;
use App\Http\Controllers\Api\V1\Admin\VehicleTypeController;
use App\Http\Controllers\Api\V1\Admin\OrganizationController;
use App\Http\Controllers\Api\V1\Admin\ContractDetailController;
use App\Http\Controllers\Api\V1\Admin\InvoiceVehicleController;
use App\Http\Controllers\Api\V1\Admin\OrganizationUserController;
use App\Http\Controllers\Api\V1\Auth\AdminAuth\AdminAuthController;
use App\Http\Controllers\Api\V1\Callback\BOA\BOACallbackController;
use App\Http\Controllers\Api\V1\Callback\CBE\CBECallbackController;
use App\Http\Controllers\Api\V1\Auth\DriverAuth\DriverAuthController;
use App\Http\Controllers\Api\V1\Auth\CustomerAuth\CustomerAuthController;
use App\Http\Controllers\Api\V1\Auth\SupplierAuth\SupplierAuthController;
use App\Http\Controllers\Api\V1\Callback\TeleBirr\TeleBirrCallbackController;
use App\Http\Controllers\Api\V1\Driver\BidController as BidForDriverController;
use App\Http\Controllers\Api\V1\Driver\TripController as TripForDriverController;
use App\Http\Controllers\Api\V1\Customer\BidController as BidForCustomerController;
use App\Http\Controllers\Api\V1\Driver\OrderController as OrderForDriverController;
use App\Http\Controllers\Api\V1\Supplier\BidController as BidForSupplierController;
use App\Http\Controllers\Api\V1\Driver\DriverController as DriverForDriverController;
use App\Http\Controllers\Api\V1\Driver\VehicleController as VehicleForDriverController;
use App\Http\Controllers\Api\V1\Supplier\OrderController as OrderForSupplierController;
use App\Http\Controllers\Api\V1\Auth\OrganizationUserAuth\OrganizationUserAuthController;
use App\Http\Controllers\Api\V1\Driver\OrderUserController as OrderUserForDriverController;
use App\Http\Controllers\Api\V1\Supplier\VehicleController as VehicleForSupplierController;
use App\Http\Controllers\Api\V1\Customer\CustomerController as CustomerForCustomerController;
use App\Http\Controllers\Api\V1\Supplier\SupplierController as SupplierForSupplierController;
use App\Http\Controllers\Api\V1\Customer\OrderUserController as OrderUserForCustomerController;
use App\Http\Controllers\Api\V1\Supplier\OrderUserController as OrderUserForSupplierController;
use App\Http\Controllers\Api\V1\OrganizationUser\TripController as TripForOrganizationController;
use App\Http\Controllers\Api\V1\Customer\InvoiceUserController as InvoiceUserForCustomerController;
use App\Http\Controllers\Api\V1\Customer\VehicleNameController as VehicleNameForCustomerController;
use App\Http\Controllers\Api\V1\Customer\VehicleTypeController as VehicleTypeForCustomerController;
use App\Http\Controllers\Api\V1\OrganizationUser\OrderController as OrderForOrganizationController;
use App\Http\Controllers\Api\V1\Supplier\VehicleNameController as VehicleNameForSupplierController;
use App\Http\Controllers\Api\V1\Supplier\VehicleTypeController as VehicleTypeForSupplierController;
use App\Http\Controllers\Api\V1\OrganizationUser\InvoiceController as InvoiceForOrganizationController;
use App\Http\Controllers\Api\V1\Supplier\InvoiceVehicleController as InvoiceVehicleForSupplierController;
use App\Http\Controllers\Api\V1\OrganizationUser\OrganizationController as OrganizationForOrganizationController;
use App\Http\Controllers\Api\V1\OrganizationUser\ContractDetailController as ContractDetailForOrganizationController;
use App\Http\Controllers\Api\V1\OrganizationUser\OrganizationUserController as OrganizationUserForOrganizationController;

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

            

            Route::prefix('roles')->group(function () {
                Route::get('/', [RoleController::class, 'index']);
                Route::post('/', [RoleController::class, 'store']);
                Route::prefix('/{role}')->group(function () {
                    Route::get('/', [RoleController::class, 'show']);
                    Route::put('/', [RoleController::class, 'update']);
                    Route::delete('/', [RoleController::class, 'destroy']);
                });
                Route::prefix('/{id}')->group(function () {
                    Route::post('/restore', [RoleController::class, 'restore']);
                });
            });


            Route::prefix('permissions')->group(function () {
                Route::get('/', [PermissionController::class, 'index']);
                Route::post('/', [PermissionController::class, 'store']);
                Route::prefix('/{permission}')->group(function () {
                    Route::get('/', [PermissionController::class, 'show']);
                    Route::put('/', [PermissionController::class, 'update']);
                    Route::delete('/', [PermissionController::class, 'destroy']);
                });
                Route::prefix('/{id}')->group(function () {
                    Route::post('/restore', [PermissionController::class, 'restore']);
                });
            });


            
            Route::prefix('constants')->group(function () {
                Route::post('/', [ConstantController::class, 'store']);
                Route::get('/', [ConstantController::class, 'index']);
                Route::prefix('/{constant}')->group(function () {
                    Route::get('/', [ConstantController::class, 'show']);
                    Route::put('/', [ConstantController::class, 'update']);
                    Route::delete('/', [ConstantController::class, 'destroy']);
                }); 
            });
            

            Route::prefix('banks')->group(function () {
                Route::post('/', [BankController::class, 'store']);
                Route::get('/', [BankController::class, 'index']);
                Route::prefix('/{bank}')->group(function () {
                    Route::get('/', [BankController::class, 'show']);
                    Route::put('/', [BankController::class, 'update']);
                    Route::delete('/', [BankController::class, 'destroy']);
                    Route::post('/restore', [BankController::class, 'restore']);
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
                    Route::put('/pay_trip', [TripController::class, 'payTrip']);
                    Route::delete('/', [TripController::class, 'destroy']);
                }); 
            });




            // routes for individual customers (for admin) are below

            Route::prefix('customers')->group(function () {
                Route::post('/', [CustomerController::class, 'store']);
                Route::get('/', [CustomerController::class, 'index']);
                Route::prefix('/{customer}')->group(function () {
                    Route::get('/', [CustomerController::class, 'show']);
                    Route::put('/', [CustomerController::class, 'update']);
                    Route::delete('/', [CustomerController::class, 'destroy']);
                }); 
            });


            Route::prefix('order_users')->group(function () {
                Route::post('/', [OrderUserController::class, 'store']);
                Route::get('/', [OrderUserController::class, 'index']);
                Route::prefix('/{orderUser}')->group(function () {
                    Route::get('/', [OrderUserController::class, 'show']);
                    Route::put('/', [OrderUserController::class, 'update']);
                    Route::put('/start_order', [OrderUserController::class, 'startOrder']);
                    Route::put('/complete_order', [OrderUserController::class, 'completeOrder']);
                    Route::delete('/', [OrderUserController::class, 'destroy']);
                }); 
            });


            // currently this invoice_users route is NOT functional
            Route::prefix('invoice_users')->group(function () {
                Route::post('/', [InvoiceUserController::class, 'store']);
                Route::get('/', [InvoiceUserController::class, 'index']);
                Route::prefix('/{invoiceUser}')->group(function () {
                    Route::get('/', [InvoiceUserController::class, 'show']);
                    Route::put('/', [InvoiceUserController::class, 'update']);
                    Route::delete('/', [InvoiceUserController::class, 'destroy']);
                }); 
            });


            // 
            Route::prefix('bids')->group(function () {
                Route::post('/', [BidController::class, 'store']);
                Route::get('/', [BidController::class, 'index']);
                Route::prefix('/{bid}')->group(function () {
                    Route::get('/', [BidController::class, 'show']);
                    Route::put('/', [BidController::class, 'update']);
                    Route::delete('/', [BidController::class, 'destroy']);
                }); 
            });


            // admin will manage the PR for vehicles in this route // i.e. see vehicle PRs (vehicle Payment Requests)
            Route::prefix('invoice_vehicles')->group(function () {
                Route::post('/', [InvoiceVehicleController::class, 'store']);
                Route::get('/', [InvoiceVehicleController::class, 'index']);
                Route::post('/', [InvoiceVehicleController::class, 'payInvoice']);
                Route::prefix('/{invoiceVehicle}')->group(function () {
                    Route::get('/', [InvoiceVehicleController::class, 'show']);
                    Route::put('/', [InvoiceVehicleController::class, 'update']);
                    Route::delete('/', [InvoiceVehicleController::class, 'destroy']);
                }); 
            });


             // currently this invoice_trips route is NOT functional
            Route::prefix('invoice_trips')->group(function () {
                Route::post('/', [InvoiceTripController::class, 'store']);
                Route::get('/', [InvoiceTripController::class, 'index']);
                Route::prefix('/{invoiceTrip}')->group(function () {
                    Route::get('/', [InvoiceTripController::class, 'show']);
                    Route::put('/', [InvoiceTripController::class, 'update']);
                    Route::delete('/', [InvoiceTripController::class, 'destroy']);
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
            //
            //
            // Route::post('/login', [OrganizationUserAuthController::class, 'login']);
            Route::post('/login_otp', [OrganizationUserAuthController::class, 'loginOtp']);
            Route::post('/verify_otp', [OrganizationUserAuthController::class, 'verifyOtp']);







            // SAMSON ADDED THIS - start
            Route::prefix('invoices')->group(function () {
                Route::get('/pay_invoices_new_open_route_get', [InvoiceForOrganizationController::class, 'payInvoicesNewOpenRouteGet']); 
            });
            // SAMSON ADDED THIS - end


        });




        Route::middleware(['auth:sanctum', 'abilities:access-organizationUser'])->group(function () {

            Route::prefix('')->group(function () {
                Route::post('/logout', [OrganizationUserAuthController::class, 'logout']);
                Route::post('/logout-all-devices', [OrganizationUserAuthController::class, 'logoutAllDevices']);
            });


            Route::prefix('organization_profile')->group(function () {
                //
                Route::prefix('/{organization}')->group(function () {
                    Route::get('/', [OrganizationForOrganizationController::class, 'show']);
                    Route::put('/', [OrganizationForOrganizationController::class, 'update']);
                }); 
            });


            Route::prefix('organization_users')->group(function () {
                Route::post('/', [OrganizationUserForOrganizationController::class, 'store']);
                Route::get('/', [OrganizationUserForOrganizationController::class, 'index']);
                Route::prefix('/{organizationUser}')->group(function () {
                    Route::get('/', [OrganizationUserForOrganizationController::class, 'show']);
                    Route::put('/', [OrganizationUserForOrganizationController::class, 'update']);
                    Route::delete('/', [OrganizationUserForOrganizationController::class, 'destroy']);
                }); 
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
            //
            //
            // Route::post('/login', [SupplierAuthController::class, 'login']);
            Route::post('/login_otp', [SupplierAuthController::class, 'loginOtp']);
            Route::post('/verify_otp', [SupplierAuthController::class, 'verifyOtp']);

        });


        Route::middleware(['auth:sanctum', 'abilities:access-supplier'])->group(function () {

            Route::prefix('')->group(function () {
                Route::post('/logout', [SupplierAuthController::class, 'logout']);
                Route::post('/logout-all-devices', [SupplierAuthController::class, 'logoutAllDevices']);
            });


            Route::prefix('supplier_profile')->group(function () {
                // 
                Route::prefix('/{supplier}')->group(function () {
                    Route::get('/', [SupplierForSupplierController::class, 'show']);
                    Route::put('/', [SupplierForSupplierController::class, 'update']);
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


            // routes for organization (for supplier) are below
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




            // routes for individual customers (for supplier) are below

            Route::prefix('order_users')->group(function () {
                Route::post('/', [OrderUserForSupplierController::class, 'store']);
                Route::get('/', [OrderUserForSupplierController::class, 'index']);
                Route::get('/index_pending', [OrderUserForSupplierController::class, 'indexPending']);
                Route::prefix('/{orderUser}')->group(function () {
                    Route::get('/', [OrderUserForSupplierController::class, 'show']);
                    Route::put('/', [OrderUserForSupplierController::class, 'update']);
                    Route::put('/start_order', [OrderUserForSupplierController::class, 'startOrder']);
                    Route::put('/complete_order', [OrderUserForSupplierController::class, 'completeOrder']);
                    Route::delete('/', [OrderUserForSupplierController::class, 'destroy']);
                }); 
            });


            Route::prefix('bids')->group(function () {
                Route::post('/', [BidForSupplierController::class, 'store']);
                Route::get('/', [BidForSupplierController::class, 'index']);
                Route::prefix('/{bid}')->group(function () {
                    Route::get('/', [BidForSupplierController::class, 'show']);
                    Route::put('/', [BidForSupplierController::class, 'update']);
                    Route::delete('/', [BidForSupplierController::class, 'destroy']);
                }); 
            });
            


            // routes for finance and related (for supplier) are below

            // supplier will manage the PR of his vehicles in this route // i.e. ask vehicle PR (vehicle Payment Request)
            Route::prefix('invoice_vehicles')->group(function () {
                Route::post('/vehicle_pr_for_order', [InvoiceVehicleForSupplierController::class, 'storeInvoiceVehicleForOrder']);
                Route::post('/vehicle_pr_for_order_user', [InvoiceVehicleForSupplierController::class, 'storeInvoiceVehicleForOrderUser']);
                Route::get('/', [InvoiceVehicleForSupplierController::class, 'index']);
                Route::prefix('/{invoiceVehicle}')->group(function () {
                    Route::get('/', [InvoiceVehicleForSupplierController::class, 'show']);
                    Route::put('/', [InvoiceVehicleForSupplierController::class, 'update']);
                    Route::delete('/', [InvoiceVehicleForSupplierController::class, 'destroy']);
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
            //
            //
            // Route::post('/login', [DriverAuthController::class, 'login']);
            Route::post('/login_otp', [DriverAuthController::class, 'loginOtp']);
            Route::post('/verify_otp', [DriverAuthController::class, 'verifyOtp']);

        });


        Route::middleware(['auth:sanctum', 'abilities:access-driver'])->group(function () {

            Route::prefix('')->group(function () {
                Route::post('/logout', [DriverAuthController::class, 'logout']);
                Route::post('/logout-all-devices', [DriverAuthController::class, 'logoutAllDevices']);
            });


            Route::prefix('driver_profile')->group(function () {
                // 
                Route::prefix('/{driver}')->group(function () {
                    Route::get('/', [DriverForDriverController::class, 'show']);
                    Route::put('/', [DriverForDriverController::class, 'update']);
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


            Route::prefix('trips')->group(function () {
                Route::post('/', [TripForDriverController::class, 'store']);
                Route::get('/', [TripForDriverController::class, 'index']);
                Route::prefix('/{trip}')->group(function () {
                    Route::get('/', [TripForDriverController::class, 'show']);
                    Route::put('/', [TripForDriverController::class, 'update']);
                    Route::delete('/', [TripForDriverController::class, 'destroy']);
                }); 
            });

            


            // routes for individual customers (for driver) are below

            Route::prefix('order_users')->group(function () {
                Route::post('/', [OrderUserForDriverController::class, 'store']);
                Route::get('/', [OrderUserForDriverController::class, 'index']);
                Route::get('/index_pending', [OrderUserForDriverController::class, 'indexPending']);
                Route::prefix('/{orderUser}')->group(function () {
                    Route::get('/', [OrderUserForDriverController::class, 'show']);
                    Route::put('/', [OrderUserForDriverController::class, 'update']);
                    Route::put('/start_order', [OrderUserForDriverController::class, 'startOrder']);
                    Route::put('/complete_order', [OrderUserForDriverController::class, 'completeOrder']);
                    Route::delete('/', [OrderUserForDriverController::class, 'destroy']);
                }); 
            });


            Route::prefix('bids')->group(function () {
                Route::post('/', [BidForDriverController::class, 'store']);
                Route::get('/', [BidForDriverController::class, 'index']);
                Route::prefix('/{bid}')->group(function () {
                    Route::get('/', [BidForDriverController::class, 'show']);
                    Route::put('/', [BidForDriverController::class, 'update']);
                    Route::delete('/', [BidForDriverController::class, 'destroy']);
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
            //
            //
            // Route::post('/login', [CustomerAuthController::class, 'login']);
            Route::post('/login_otp', [CustomerAuthController::class, 'loginOtp']);
            Route::post('/verify_otp', [CustomerAuthController::class, 'verifyOtp']);







            // SAMSON ADDED THIS - start
            Route::prefix('bids')->group(function () {
                Route::prefix('/{bid}')->group(function () {
                    Route::get('/accept_bid/{id}/{paymentMethod}', [BidForCustomerController::class, 'acceptBidNew']);
                }); 
            });

            Route::prefix('invoice_users')->group(function () {
                Route::get('/pay_invoice_final_new_open_route_get', [InvoiceUserForCustomerController::class, 'payInvoiceFinalNewOpenRouteGet']); // Bad idea // delete later // remove later
            });
            // SAMSON ADDED THIS - end



        });




        Route::middleware(['auth:sanctum', 'abilities:access-customer'])->group(function () {

            Route::prefix('')->group(function () {
                Route::post('/logout', [CustomerAuthController::class, 'logout']);
                Route::post('/logout-all-devices', [CustomerAuthController::class, 'logoutAllDevices']);
            });



            // currently this customers route is NOT functional, 
            // because customer is registering in the open routes, and when they logs in i will return their full info
            Route::prefix('customer_profile')->group(function () {
                // 
                Route::prefix('/{customer}')->group(function () {
                    Route::get('/', [CustomerForCustomerController::class, 'show']);
                    Route::put('/', [CustomerForCustomerController::class, 'update']);
                }); 
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



            Route::prefix('order_users')->group(function () {
                Route::post('/', [OrderUserForCustomerController::class, 'store']);
                Route::get('/', [OrderUserForCustomerController::class, 'index']);
                Route::prefix('/{orderUser}')->group(function () {
                    Route::get('/', [OrderUserForCustomerController::class, 'show']);
                    Route::put('/', [OrderUserForCustomerController::class, 'update']);
                    Route::delete('/', [OrderUserForCustomerController::class, 'destroy']);
                }); 
            });

            

            Route::prefix('bids')->group(function () {
                Route::post('/', [BidForCustomerController::class, 'store']);
                Route::get('/', [BidForCustomerController::class, 'index']);
                Route::prefix('/{bid}')->group(function () {
                    Route::get('/', [BidForCustomerController::class, 'show']);
                    Route::put('/', [BidForCustomerController::class, 'update']);
                    Route::put('/accept_bid', [BidForCustomerController::class, 'acceptBid']);
                    Route::delete('/', [BidForCustomerController::class, 'destroy']);
                }); 
            });


            Route::prefix('invoice_users')->group(function () {
                Route::post('/', [InvoiceUserForCustomerController::class, 'store']);
                Route::get('/', [InvoiceUserForCustomerController::class, 'index']);
                Route::post('/pay_invoice_final', [InvoiceUserForCustomerController::class, 'payInvoiceFinal']);
                Route::get('/pay_invoice_final', [InvoiceUserForCustomerController::class, 'payInvoiceFinal']); // Bad idea // delete later // remove later
                Route::prefix('/{invoiceUser}')->group(function () {
                    Route::get('/', [InvoiceUserForCustomerController::class, 'show']);
                    Route::put('/', [InvoiceUserForCustomerController::class, 'update']);
                    Route::delete('/', [InvoiceUserForCustomerController::class, 'destroy']);
                }); 
            });



            



        });

    });







    // Callback Routes from banks and financial institutions
    // These are C2B (i.e. C to B) callbacks
    Route::prefix('call_backs')->group(function () {

        Route::prefix('boa')->group(function () {
            Route::post('/pay_invoices_call_back', [BOACallbackController::class, 'payInvoicesCallback']);
        });

        Route::prefix('cbe')->group(function () {
            Route::post('/pay_invoices_call_back', [CBECallbackController::class, 'payInvoicesCallback']);
        });
            
        Route::prefix('tele_birr')->group(function () {
            Route::post('/pay_invoices_call_back', [TeleBirrCallbackController::class, 'payInvoicesCallback']);
        });

    });

        




    // TEST OPEN ROUTES (normal_endpoint + _open_route)
        // initiate payment test C to B (C2B)
            Route::get('/pay_invoices_test_open_route_boa', [InvoiceForOrganizationController::class, 'testboa']);
            Route::get('/pay_invoices_test_open_route_telebirr_apply_fabric_token', [InvoiceForOrganizationController::class, 'testTelebirrApplyFabricToken']);
            Route::get('/pay_invoices_test_open_route_telebirr', [InvoiceForOrganizationController::class, 'testTelebirr']);

        // initiate payment test B to C (B2C)
            Route::get('/pay_invoice_test_open_route_telebirr_b2c', [InvoiceVehicleController::class, 'testTelebirrB2C']);
            // the below is Test code that could read Telebirr Response XML code after B2C request
            Route::get('/pay_invoice_test_open_route_telebirr_b2c_read_returned_xml', [InvoiceVehicleController::class, 'testTelebirrB2CReadReturnedXml']);

        




});