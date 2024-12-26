<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Carbon\Carbon;
use App\Models\Bid;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Constant;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\OrderUser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\BidResources\BidResource;
use App\Http\Requests\Api\V1\AdminRequests\StoreBidRequest;
use App\Http\Requests\Api\V1\AdminRequests\StoreOrderUserRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateOrderUserRequest;
use App\Http\Resources\Api\V1\OrderUserResources\OrderUserResource;


class OrderUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', OrderUser::class);

        /* $validatedData = */ $request->validate([
            'order_status_search' => [
                'sometimes', 'string', Rule::in([OrderUser::ORDER_STATUS_PENDING, OrderUser::ORDER_STATUS_SET, OrderUser::ORDER_STATUS_START, OrderUser::ORDER_STATUS_COMPLETE]),
            ],
            // Other validation rules if needed
        ]);


        $ordersUsers = OrderUser::whereNotNull('id');

        // use Filtering service OR Scope to do this
        if ($request->has('customer_id_search')) {
            if (isset($request['customer_id_search'])) {
                $customerId = $request['customer_id_search'];

                $ordersUsers = $ordersUsers->where('customer_id', $customerId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('supplier_id_search')) {
            if (isset($request['supplier_id_search'])) {
                $supplierId = $request['supplier_id_search'];

                $ordersUsers = $ordersUsers->where('supplier_id', $supplierId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('driver_id_search')) {
            if (isset($request['driver_id_search'])) {
                $driverId = $request['driver_id_search'];

                $ordersUsers = $ordersUsers->where('driver_id', $driverId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('vehicle_id_search')) {
            if (isset($request['vehicle_id_search'])) {
                $vehicleId = $request['vehicle_id_search'];

                $ordersUsers = $ordersUsers->where('vehicle_id', $vehicleId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('order_code_search')) {
            if (isset($request['order_code_search'])) {
                $orderCode = $request['order_code_search'];

                $ordersUsers = $ordersUsers->where('order_code', $orderCode);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('is_terminated_search')) {
            if (isset($request['is_terminated_search'])) {
                $isTerminated = $request['is_terminated_search'];

                $ordersUsers = $ordersUsers->where('is_terminated', $isTerminated);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('order_status_search')) {
            if (isset($request['order_status_search'])) {
                $orderStatus = $request['order_status_search'];

                $ordersUsers = $ordersUsers->where('status', $orderStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }
        }
        if ($request->has('paid_complete_status_search')) {
            if (isset($request['paid_complete_status_search'])) {
                $paidCompleteStatus = $request['paid_complete_status_search'];

                $ordersUsers = $ordersUsers->where('paid_complete_status', $paidCompleteStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }
        }


        $ordersUsersData = $ordersUsers->with('customer', 'vehicleName', 'vehicle', 'supplier', 'driver')->latest()->paginate(FilteringService::getPaginate($request));       // this get multiple orders of customers

        return OrderUserResource::collection($ordersUsersData);


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderUserRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {
            
            if ($request->has('orders')) {

                $orderIds = [];
                    // since multiple orders can be sent at once 
                        // i will put similar order_code in OrderUserController = for those multiple orders that are sent at once
                        //
                // Generate a random order code
                $uniqueCode = Str::random(20); // Adjust the length as needed

                // Check if the generated code already exists in the database
                while (OrderUser::where('order_code', $uniqueCode)->exists()) {
                    $uniqueCode = Str::random(20); // Regenerate the code if it already exists
                }


                $customer = Customer::find($request['customer_id']);

                if ($customer->is_active !== 1) {
                    return response()->json(['message' => 'Not Active. this customer account is not Active, so you can not make order for this customer'], 428); 
                }

                if ($customer->is_approved !== 1) {
                    return response()->json(['message' => 'Not Approved. this customer account is not Approved, so you can not make order for this customer'], 428); 
                }


                // Now do operations on each of the orders sent
                foreach ($request->safe()->orders as $requestData) {

                    // CHECK REQUEST DATEs (Order dates)

                    // FIRST OF ALL = Check if start_date and end_date are valid dates
                    if (!strtotime($requestData['start_date']) || !strtotime($requestData['end_date'])) {
                        return response()->json(['message' => 'Invalid date format.'], 400);
                    }



                    // order dates // from the request
                    $orderRequestStartDate = Carbon::parse($requestData['start_date'])->toDateString();
                    $orderRequestEndDate = Carbon::parse($requestData['end_date'])->toDateString();


                    // todays date
                    $today = now()->format('Y-m-d');


                    // order start date = must be today or in the days after today , (but start date can not be before today)
                    // Check if start_date is greater than or equal to todays date
                    if ($orderRequestStartDate < $today) {
                        return response()->json(['message' => 'Order Start date must be greater than or equal to today\'s date.'], 400);
                    }
                    // order end date = must be today or in the days after today , (but end date can not be before today)
                    // Check if end_date is greater than or equal to todays date
                    if ($orderRequestEndDate < $today) {
                        return response()->json(['message' => 'Order End date must be greater than or equal to today\'s date.'], 400);
                    }



                    if ($orderRequestStartDate > $orderRequestEndDate) {
                        return response()->json(['message' => 'Order Start Date should not be greater than the Order End Date'], 400);
                    }



                    $orderUser = OrderUser::create([
                        'order_code' => $uniqueCode,

                        'customer_id' => $request['customer_id'],
                        
                        'vehicle_name_id' => $requestData['vehicle_name_id'],

                        'vehicle_id' => null,   // is NULL when the order is created initially
                        'driver_id' => null,    // is NULL when the order is created initially
                        'supplier_id' => null,    // is NULL when the order is created initially

                        'start_date' => $requestData['start_date'],
                        'begin_date' => null,                           // is NULL when the order is created initially, // and set when the order is started
                        'end_date' => $requestData['end_date'],

                        'start_location' => $requestData['start_location'],
                        'end_location' => $requestData['end_location'],

                        'start_latitude' => $requestData['start_latitude'],
                        'start_longitude' => $requestData['start_longitude'],
                        'end_latitude' => $requestData['end_latitude'],
                        'end_longitude' => $requestData['end_longitude'],

                        'status' => OrderUser::ORDER_STATUS_PENDING,    // is PENDING when order is created initially

                        'is_terminated' => 0,    // is 0 (false) when order is created initially
                        'original_end_date' => $requestData['end_date'], // this always holds the end_date of the order as backup, incase the order is terminated.   
                                                                        // if the order is terminated the end_date will be assigned the termination_date.      // So (original_end_date) holds the original order (end_date) as backup 
        
                        'price_total' => null,    // is NULL when the order is created initially
                        'paid_complete_status' => 0,    // is 0 (false) when order is created initially

                        'vehicle_pr_status' => null,

                        'order_description' => $requestData['order_description'],

                        'with_driver' => (int) (isset($requestData['with_driver']) ? $requestData['with_driver'] : 0),
                        'with_fuel' => 0,
                        'periodic' => (int) (isset($requestData['periodic']) ? $requestData['periodic'] : 0),

                    ]);

                    $orderIds[] = $orderUser->id;



                }

                // WORKS
                $orders = OrderUser::whereIn('id', $orderIds)->with('customer', 'vehicleName', 'vehicle', 'supplier', 'driver', 'bids', 'invoiceUsers')->latest()->get();       // this get the orders created here
                return OrderUserResource::collection($orders);


            }


            
        });

        return $var;

    }

    /**
     * Display the specified resource.
     */
    public function show(OrderUser $orderUser)
    {
        $this->authorize('view', $orderUser);

        return OrderUserResource::make($orderUser->load('customer', 'vehicleName', 'vehicle', 'supplier', 'driver', 'bids', 'invoiceUsers'));
    }



    




    /**
     * Update the specified resource in storage.
     * 
     * to Start Order = ORDER_STATUS_START
     * 
     * 
     */
    public function startOrder(OrderUser $orderUser)
    {
        // do AUTH here or in the controller

        // AUTOMATIC : - here we will make the vehicle is_available = VEHICLE_ON_TRIP       - ALSO order begin_date will be set to today()      - ALSO order status will be ORDER_STATUS_START

        $var = DB::transaction(function () use ($orderUser) {


            // if ADIAMT wants to rent their own vehicles, They Can Register as SUPPLIERs Themselves
            // if (!$orderUser->driver && !$orderUser->supplier) { 
            //     return response()->json(['message' => 'the order at least should be accepted by either a driver or supplier'], 428); 
            // }


            if ($orderUser->driver) {
                if ($orderUser->driver->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Driver'], 428); 
                }
                if ($orderUser->driver->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Driver'], 428); 
                }
            }
            if ($orderUser->supplier) {
                if ($orderUser->supplier->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Supplier'], 428); 
                }
                if ($orderUser->supplier->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Supplier'], 428); 
                }
            }


            // this is MANDATORY 
            //  - a customer, can Accept MULTIPLE orders using a ONE SIMILAR vehicle.      // - BUT they can NOT Start MULTIPLE orders using that one similar vehicle
            //  - a single vehicle can accept multiple orders                                               // - BUT a single vehicle can NOT start multiple orders
            //
            // so in the above scenario 
            // when we ACCEPT order that vehicle 'is_available' attribute is NOT changed
            // BUT when we START order that vehicle 'is_available' attribute will be changed to - is_available=VEHICLE_ON_TRIP
            //
            // so considering the above scenario, this if condition is done so that
            // //
            // so that a single vehicle can NOT be used to start multiple orders       (IMPORTANT condition)
            //      // IT means we check the vehicle 'is_available' every time we start an order, so that a single vehicle can NOT be used to start multiple orders
            if ($orderUser->vehicle->is_available !== Vehicle::VEHICLE_AVAILABLE) {
                return response()->json(['message' => 'the selected vehicle is not currently available'], 409); 
            }


            if ($orderUser->status !== OrderUser::ORDER_STATUS_SET) {
                return response()->json(['message' => 'this order is not SET (ACCEPTED). order should be SET (ACCEPTED) before it can be STARTED.'], 428); 
            }

            if ($orderUser->end_date < today()->toDateString()) {
                return response()->json(['message' => 'this order is Expired already.'], 410); 
            }

            if ($orderUser->is_terminated !== 0) {
                return response()->json(['message' => 'this order is Terminated'], 410); 
            }



            if ($orderUser->with_fuel === 1) { 
                return response()->json(['message' => 'this order requires fuel to be filled by adiamat. so it needs a driver to fill log-sheet for every trip. therefore the order needs a driver account be started, so a driver can only start this order'], 410); 
            }

            // check abrham samson
            // is the following condition required 
            // if ($orderUser->periodic === 1) { 
            //     return response()->json(['message' => 'this order is periodic. so the order needs a driver to be started'], 422); 
            // }




            
            // todays date
            $today = now()->format('Y-m-d');

            $orderStartDate = Carbon::parse($orderUser->start_date)->toDateString();

            if ($orderStartDate > $today) {
                return response()->json(['message' => 'this order can not be made to begin now yet. the start date of the order is still in the future. you must wait another days and reach the start date of the order to start it.'], 400);
            }            



            $success = $orderUser->update([
                'status' => OrderUser::ORDER_STATUS_START,
                'begin_date' => today()->toDateString(),
            ]);
            //
            if (!$success) {
                return response()->json(['message' => 'Order Update Failed'], 500);
            }

            $vehicle = Vehicle::find($orderUser->vehicle_id);
            //
            if (!$vehicle) {
                return response()->json(['message' => 'we could not find the actual vehicle of the vehicle_id in this order'], 404);
            }

            $successTwo = $vehicle->update([
                'is_available' => Vehicle::VEHICLE_ON_TRIP,
            ]);
            //
            if (!$successTwo) {
                return response()->json(['message' => 'Vehicle Update Failed'], 500);
            }

            $updatedOrderUser = OrderUser::find($orderUser->id);
                
            return OrderUserResource::make($updatedOrderUser->load('customer', 'vehicleName', 'vehicle', 'supplier', 'driver', 'bids', 'invoiceUsers'));

        });

        return $var;

    }



    /**
     * Update the specified resource in storage.
     * 
     * to Start Order = ORDER_STATUS_COMPLETE
     * 
     * 
     */
    public function completeOrder(OrderUser $orderUser)
    {
        // do AUTH here or in the controller

        // AUTOMATIC : - here we will make the vehicle is_available = VEHICLE_AVAILABLE // order status = ORDER_STATUS_COMPLETE // and order end_date = today()

        $var = DB::transaction(function () use ($orderUser) {

            if ($orderUser->status !== OrderUser::ORDER_STATUS_START) {
                return response()->json(['message' => 'this order is not STARTED. order should be STARTED before it can be COMPLETED.'], 428); 
            }



            /*
                The reason i did the following TWO conditions is because of PR asking. and for the following TWO scenarios
                        //
                        NOW
                    1. if a we COMPLETE an order before it's intended end_date, 
                                    - then NO Problem, - there sill NOT be any problems caused by this scenario
                             => SO the end_date of the order will be the date the order is completed.    i.e. in other words the order end_date will be today() [today being the date the order is being completed].
                        //
                        //
                        BUT
                    2. if a we COMPLETE an order way after it's intended end_date (or Complete the order on a date that is EQUAL to its intended end_date), 
                                    - that will create extra days that the organization is asked PR by Adiamat
                                    - that will create extra days that the vehicle (supplier) itself is payed by Adiamat
                             => SO the end_date of the order will be the already existing end_date of the order.    i.e. in other words the order end_date will NOT be changed.
            */
            //
            //
            // todays date
            $today = now()->format('Y-m-d');
            //
            // 
            // we do this Because if the order end date is in the future still and we sent ORDER_STATUS_COMPLETE, the project still charges the the customer for the remaining days until the project end_date is reached, even if the "order status is set to COMPLETE"
            // solution is the above, if we make the order end date = today(), when order is set to Complete , then the order end_date will match the order Complete status,  and there will not be any left over dates we ask payment to after the order is complete
            //
            //
            //
            $orderUserEndDate = Carbon::parse($orderUser->end_date)->toDateString();
            //
            //
            //
            // if a we COMPLETE an order before it's intended end_date,   - or -   if the order that is about to be completed does NOT reach its end_date,
            // if today's date does is less than the order's end_date
            //
            // => SO the end_date of the order will be the date the order is completed.    i.e. in other words the order end_date will be today() [today being the date the order is being completed].
            if ($today < $orderUserEndDate) {

                $success = $orderUser->update([
                    'status' => OrderUser::ORDER_STATUS_COMPLETE,
                    'end_date' => $today,
                ]);
                //
                if (!$success) {
                    return response()->json(['message' => 'Order Update Failed'], 500);
                }

            }
            //
            // if a we COMPLETE an order way after it's intended end_date (or Complete the order on a date that is EQUAL to its intended end_date),   - or -   if the order that is about to be completed has already past its end_date (or at least its on its end_date (EQUALs to its end_date) )
            // if today's date is greater than (or equals to end date) the order's end_date
            //
            //  => SO the end_date of the order will be the already existing end_date of the order.    i.e. in other words the order end_date will NOT be changed.
            if ($today >= $orderUserEndDate) {

                $success = $orderUser->update([
                    'status' => OrderUser::ORDER_STATUS_COMPLETE,
                ]);
                //
                if (!$success) {
                    return response()->json(['message' => 'Order Update Failed'], 500);
                }

            }

            

            $vehicle = Vehicle::find($orderUser->vehicle_id);
            //
            if (!$vehicle) {
                return response()->json(['message' => 'we could not find the actual vehicle of the vehicle_id in this order'], 404);
            }

            $successTwo = $vehicle->update([
                'is_available' => Vehicle::VEHICLE_AVAILABLE,
            ]);
            //
            if (!$successTwo) {
                return response()->json(['message' => 'Vehicle Update Failed'], 500);
            }

            $updatedOrderUser = OrderUser::find($orderUser->id);
                
            return OrderUserResource::make($updatedOrderUser->load('customer', 'vehicleName', 'vehicle', 'supplier', 'driver', 'bids', 'invoiceUsers'));

        });

        return $var;

    }




    


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderUserRequest $request, OrderUser $orderUser)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderUser $orderUser)
    {
        //
    }
}
