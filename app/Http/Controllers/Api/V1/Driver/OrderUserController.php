<?php

namespace App\Http\Controllers\Api\V1\Driver;

use Carbon\Carbon;
use App\Models\Bid;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Constant;
use App\Models\OrderUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\DriverRequests\StoreBidRequest;
use App\Http\Resources\Api\V1\BidResources\BidForDriverResource;
use App\Http\Resources\Api\V1\OrderUserResources\OrderUserForDriverResource;

class OrderUserController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * Display All Past Orders this Driver is already involved with,
     *      THIS MEANS = the logged in Driver can see a list of orders from order_users table that have his driver_id
     * 
     * this orders can be filtered with Order Status = ORDER_STATUS_PENDING, ORDER_STATUS_SET, ORDER_STATUS_START, ORDER_STATUS_COMPLETE
     * or all of them can be listed without filtering, depending on if the frontend passes the order_status_search parameter on the url
     * 
     */
    public function index(Request $request)
    {
        //
        /* $validatedData = */ $request->validate([
            'order_status_search' => [
                'sometimes', 'string', Rule::in([OrderUser::ORDER_STATUS_PENDING, OrderUser::ORDER_STATUS_SET, OrderUser::ORDER_STATUS_START, OrderUser::ORDER_STATUS_COMPLETE]),
            ],
            // Other validation rules if needed
        ]);

        
        // get the logged in driver identity
        $user = auth()->user();
        $driver = Driver::find($user->id);


        // list all the orders that the logged in driver have involved with in the past
        $ordersUsers = OrderUser::where('driver_id', $driver->id);

        

        if ($request->has('order_status_search')) {
            if (isset($request['order_status_search'])) {
                $orderStatus = $request['order_status_search'];

                $ordersUsers = $ordersUsers->where('status', $orderStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }

        }
        

        
        // check if the pagination works overall in this orders data list
        $ordersUsersData = $ordersUsers->with('vehicleName', 'vehicle', 'supplier', 'customer')
            ->latest()
            ->paginate(FilteringService::getPaginate($request)); // get list of orders that fits this logged in driver // get orders that the logged in driver is already involved with in the past


        return OrderUserForDriverResource::collection($ordersUsersData);
    }



    /**
     * Display a listing of the resource.
     * 
     * Display PENDING Orders that can be accepted by this Driver
     * 
     * only PENDING orders
     */
    public function indexPending(Request $request)
    {
        // get the logged in driver identity
        $user = auth()->user();
        $driver = Driver::find($user->id);


        // list all the vehicles of the logged in driver
        $vehicles = Vehicle::where('driver_id', $driver->id)
            ->where('is_available', Vehicle::VEHICLE_AVAILABLE)
            ->latest()
            ->get();       // this a single vehicle of the logged in driver which he is related to with driver_id // will only get One vehicle // which have with driver 1 or 0

        
        // $vehicleNameIds = $vehicles->pluck('vehicle_name_id');
        $vehicleNameIds = $vehicles->pluck('vehicle_name_id')->unique();  
        

        $ordersUsers = OrderUser::where('status', OrderUser::ORDER_STATUS_PENDING);


        // check if the pagination works overall in this orders data list
        $ordersUsersData = $ordersUsers->whereIn('vehicle_name_id', $vehicleNameIds)
            ->where('is_terminated', 0)
            ->whereDate('end_date', '>=', today()->toDateString()) // toDateString() is used , to get and use only the date value of today(), // so the time value is stripped out
            ->with('vehicleName', 'vehicle', 'supplier', 'customer')
            ->latest()
            ->paginate(FilteringService::getPaginate($request)); // get list of orders that fits this logged in driver


        return OrderUserForDriverResource::collection($ordersUsersData);

    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(OrderUser $orderUser)
    {
        //
    }








    /**
     * Update the specified resource in storage.
     * 
     * to Start Order = ORDER_STATUS_START
     * 
     * when the Driver (with the vehicle) Reaches the order maker of the customer at the Order start location
     */
    public function startOrder(OrderUser $orderUser)
    {
        // AUTOMATIC : - here we will make the vehicle is_available = VEHICLE_ON_TRIP         - ALSO order begin_date will be set to today()      - ALSO order status will be ORDER_STATUS_START

        $var = DB::transaction(function () use ($orderUser) {

            // get the driver identity
            $user = auth()->user();
            $driver = Driver::find($user->id);

            if ($orderUser->driver_id !== $driver->id) {
                return response()->json(['message' => 'invalid Order is selected. Deceptive request Aborted.'], 403); 
            }
            //
            // mandatory for the following if conditions, BUT do NOT do this check when super_admin starts an order
            if (!$orderUser->driver) { 
                return response()->json(['message' => 'this order needs a driver to be started'], 422); 
            }

            // if ADIAMT wants to rent their own vehicles, They Can Register as SUPPLIERs Themselves // but i commented the below // so check abrham samson
            // if (!$order->supplier) { 
            //     return response()->json(['message' => 'this order needs a supplier to be started'], 422); 
            // }

            if ($orderUser->driver) {
                if ($orderUser->driver->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Driver'], 428); 
                }
                if ($orderUser->driver->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Driver'], 401); 
                }
            }
            if ($orderUser->supplier) {
                if ($orderUser->supplier->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Supplier'], 428); 
                }
                if ($orderUser->supplier->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Supplier'], 403); 
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
                
            return OrderUserForDriverResource::make($updatedOrderUser->load('vehicleName', 'vehicle', 'supplier', 'customer'));

        });

        return $var;
    }



    /**
     * Update the specified resource in storage.
     * 
     * to Start Order = ORDER_STATUS_COMPLETE
     * 
     * when the Driver Finishes a trip or all trips, and the customer need for that vehicle is fulfilled by the Driver
     */
    public function completeOrder(OrderUser $orderUser)
    {
        // AUTOMATIC : - here we will make the vehicle is_available = VEHICLE_AVAILABLE // order status = ORDER_STATUS_COMPLETE // and order end_date = today()

        $var = DB::transaction(function () use ($orderUser) {

            // get the driver identity
            $user = auth()->user();
            $driver = Driver::find($user->id);

            if ($orderUser->driver_id !== $driver->id) {
                return response()->json(['message' => 'invalid Order is selected. Deceptive request Aborted.'], 403); 
            }
            

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
                
            return OrderUserForDriverResource::make($updatedOrderUser->load('vehicleName', 'vehicle', 'supplier', 'customer'));

        });

        return $var;

    }


    


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrderUser $orderUser)
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
