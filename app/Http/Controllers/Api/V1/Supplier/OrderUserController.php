<?php

namespace App\Http\Controllers\Api\V1\Supplier;

use Carbon\Carbon;
use App\Models\Bid;
use App\Models\Vehicle;
use App\Models\Constant;
use App\Models\Supplier;
use App\Models\OrderUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\SupplierRequests\StoreBidRequest;
use App\Http\Resources\Api\V1\BidResources\BidForSupplierResource;
use App\Http\Resources\Api\V1\OrderUserResources\OrderUserForSupplierResource;

class OrderUserController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * Display All Past Orders this Supplier is already involved with,
     *      THIS MEANS = the logged in Supplier can see a list of orders from order_users table that have his supplier_id
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


        // get the logged in supplier identity
        $user = auth()->user();
        $supplier = Supplier::find($user->id);


        // list all the orders that the logged in supplier have involved with in the past
        $ordersUsers = OrderUser::where('supplier_id', $supplier->id);

        

        if ($request->has('order_status_search')) {
            if (isset($request['order_status_search'])) {
                $orderStatus = $request['order_status_search'];

                $ordersUsers = $ordersUsers->where('status', $orderStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }

        }
        

        
        // check if the pagination works overall in this orders data list
        $ordersUsersData = $ordersUsers->with('vehicleName', 'vehicle', 'driver')
            ->latest()
            ->paginate(FilteringService::getPaginate($request)); // get list of orders that fits this logged in supplier // get orders that the logged in supplier is already involved with in the past


        return OrderUserForSupplierResource::collection($ordersUsersData);

        
    }


    /**
     * Display a listing of the resource.
     * 
     * Display PENDING Orders that can be accepted by this supplier
     * 
     * only PENDING orders
     */
    public function indexPending(Request $request)
    {
        // get the logged in supplier identity
        $user = auth()->user();
        $supplier = Supplier::find($user->id);


        // list all the vehicles of the logged in supplier
        $vehicles = Vehicle::where('supplier_id', $supplier->id)
            ->where('is_available', Vehicle::VEHICLE_AVAILABLE)
            ->where('with_driver', 0)
            ->get();       // this get multiple vehicles of the supplier // which have with driver 0

        
        // from the vehicles of the logged in supplier lets get all the vehicle_name_ids separately
        // $vehicleNameIds = $vehicles->pluck('vehicle_name_id'); // if there are similar vehicle_name_id // this will contain them as duplicate // so we use the following, it will eliminate any duplicate vehicle_name_ids
        //
        // ABRHAM CHECK , SAMSON // here we must REMOVE the duplicated vehicle_name ids from the vehicle_name_id list // USING THE FOLLOWING
        // By chaining the unique method after pluck, I will get an array containing only unique vehicle_name_id values. This ensures that I do not have duplicates in the resulting list
        // i can utilize the unique method in Laravel collections. 
        $vehicleNameIds = $vehicles->pluck('vehicle_name_id')->unique();    
        

        $ordersUsers = OrderUser::where('status', OrderUser::ORDER_STATUS_PENDING);

    
        // check if the pagination works overall in this orders data list
        $ordersUsersData = $ordersUsers->whereIn('vehicle_name_id', $vehicleNameIds)
            ->where('is_terminated', 0)
            ->whereDate('end_date', '>=', today()->toDateString()) // toDateString() is used , to get and use only the date value of today(), // so the time value is stripped out
            ->with('vehicleName', 'vehicle', 'driver')
            ->latest()
            ->paginate(FilteringService::getPaginate($request)); // get list of orders that fits this logged in supplier


        return OrderUserForSupplierResource::collection($ordersUsersData);

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
     * Store a newly created resource in storage.
     */
    public function storeBid(StoreBidRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {
            // get the supplier identity
            $user = auth()->user();
            $supplier = Supplier::find($user->id);

            $vehicle = Vehicle::find($request['vehicle_id']);
            $orderUser = OrderUser::find($request['order_id']);
            

            if ($vehicle->supplier_id !== $supplier->id) {
                return response()->json(['message' => 'invalid Vehicle is selected. or This Supplier does not have a vehicle with this id. Deceptive request Aborted.'], 403); 
            }

            if ($vehicle->vehicle_name_id !== $orderUser->vehicle_name_id) {
                return response()->json(['message' => 'invalid Vehicle is selected. or The Selected Vehicle does not match the orders requirement (the selected vehicle vehicle_name_id is NOT equal to the order vehicle_name_id). Deceptive request Aborted.'], 403); 
            }

            if (Bid::where('vehicle_id', $vehicle->id)->exists()) {
                return response()->json(['message' => 'you already bid for this order with this vehicle'], 403); 
            }

            if ($vehicle->is_available !== Vehicle::VEHICLE_AVAILABLE) {
                return response()->json(['message' => 'the selected vehicle is not currently available'], 403); 
            }



            if ($supplier->is_active != 1) {
                return response()->json(['message' => 'Forbidden: Deactivated Supplier'], 403); 
            }
            if ($supplier->is_approved != 1) {
                return response()->json(['message' => 'Forbidden: NOT Approved Supplier'], 403); 
            }











            if ($orderUser->status !== OrderUser::ORDER_STATUS_PENDING) {
                return response()->json(['message' => 'this order is not pending. it is already accepted , started or completed'], 403); 
            }

            if ($orderUser->end_date < today()->toDateString()) {
                return response()->json(['message' => 'this order is Expired already.'], 403); 
            }

            if ($orderUser->is_terminated !== 0) {
                return response()->json(['message' => 'this order is Terminated'], 403); 
            }
            
            if (($orderUser->vehicle_id !== null) || ($orderUser->driver_id !== null) || ($orderUser->supplier_id !== null)) {
                return response()->json(['message' => 'this order is already being accepted and it already have a value on the columns (driver_id or supplier_id or vehicle_id) , for some reason'], 403); 
            }


            if ($orderUser->with_driver === 1) {
                return response()->json(['message' => 'a supplier can not accept this order, since this order requires driver.'], 403);
            }

            if ($orderUser->with_fuel === 1) {
                return response()->json(['message' => 'a supplier can not accept this order, since this order requires fuel. so this order can only be accepted with a driver account.'], 403);
            }

            

            if ($vehicle->with_driver !== $orderUser->with_driver) {

                if (($vehicle->with_driver === 1) && ($orderUser->with_driver === 0)) {
                    return response()->json(['message' => 'the order does not need a driver'], 403); 
                }
                else if (($vehicle->with_driver === 0) && ($orderUser->with_driver === 1)) {
                    return response()->json(['message' => 'the order needs vehicle with a driver'], 403); 
                }
                

                return response()->json(['message' => 'the vehicle with_driver value is not equal with that of the order requirement.'], 403); 
                
            }

            // this if is important and should be right here 
            // this if should NOT be nested in any other if condition // this if should be independent and done just like this  // this if should be checked independently just like i did it right here
            if (($vehicle->driver_id === null) && ($orderUser->with_driver === 1)) {
                return response()->json(['message' => 'the vehicle you selected for the order does not have actual driver currently. This Order Needs Vehicle that have Driver'], 403); 
            }
            

            // calculate the initial payment for this bid entry
            $priceTotalFromRequest = (int) $request['price_total'];
            
            $constant = Constant::where('title', Constant::ORDER_USER_INITIAL_PAYMENT_PERCENT)->first();
            //
            if (!$constant) {
                return response()->json(['message' => 'initial payment percent for individual customers orders is not found table.  ORDER_USER_INITIAL_PAYMENT_PERCENT from constants table does not exist'], 403); 
            }
            // check if $constant->percent_value is NULL
            if ($constant->percent_value === null) {
                return response()->json([
                    'message' => 'Invalid percent value retrieved from the constants table. The percent value can not be null.'
                ], 403);
            }
            // Check if the percent value is within the valid range
            if ($constant->percent_value < 1 || $constant->percent_value > 100) {
                return response()->json([
                    'message' => 'Invalid percent value retrieved from the constants table. The percent value must be between 1 and 100.'
                ], 403);
            }
            $orderUserInitialPaymentPercentConstant = $constant->percent_value;
            $initialPaymentMultiplierConstant = ((int) $orderUserInitialPaymentPercentConstant)/100;

            $priceInitial = $priceTotalFromRequest * $initialPaymentMultiplierConstant;
            
            
            $bid = Bid::create([
                'order_id' => $request['order_id'],
                'vehicle_id' => $request['vehicle_id'],

                'price_total' => $request['price_total'],
                'price_initial' => $priceInitial,
            ]);
            //
            if (!$bid) {
                return response()->json(['message' => 'Bid Create Failed'], 422);
            }


            $bidValue = Bid::find($bid->id);


            return BidForSupplierResource::make($bidValue->load('orderUser'));
                 
        });

        return $var;
    }





    /**
     * Update the specified resource in storage.
     * 
     * to Start Order = ORDER_STATUS_START
     * 
     * when the vehicle departs from the supplier
     */
    public function startOrder(OrderUser $orderUser)
    {
        // AUTOMATIC : - here we will make the vehicle is_available = VEHICLE_ON_TRIP           - ALSO order begin_date will be set to today()      - ALSO order status will be ORDER_STATUS_START

        $var = DB::transaction(function () use ($orderUser) {

            // get the supplier identity
            $user = auth()->user();
            $supplier = Supplier::find($user->id);

            if ($orderUser->supplier_id !== $supplier->id) {
                return response()->json(['message' => 'invalid Order is selected. Deceptive request Aborted.'], 403); 
            }
            //
            // redundant
            if (!$orderUser->supplier) { 
                return response()->json(['message' => 'this order needs a supplier to be started'], 403); 
            }

            
            if ($orderUser->supplier) {
                if ($orderUser->supplier->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Supplier'], 403); 
                }
                if ($orderUser->supplier->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Supplier'], 403); 
                }
            }


            if ($orderUser->status !== OrderUser::ORDER_STATUS_SET) {
                return response()->json(['message' => 'this order is not SET (ACCEPTED). order should be SET (ACCEPTED) before it can be STARTED.'], 403); 
            }

            if ($orderUser->end_date < today()->toDateString()) {
                return response()->json(['message' => 'this order is Expired already.'], 403); 
            }

            if ($orderUser->is_terminated !== 0) {
                return response()->json(['message' => 'this order is Terminated'], 403); 
            }


            if ($orderUser->with_driver === 1) {
                return response()->json(['message' => 'a supplier can not start this order, since this order requires driver.'], 403);
            }

            if ($orderUser->with_fuel === 1) {
                return response()->json(['message' => 'a supplier can not start this order, since this order requires fuel. so this order can only be started with a driver account.'], 403);
            }

            // NOT NEEDED since we checked with_driver above
            // if ($orderUser->driver) { 
            //     return response()->json(['message' => 'this order have a driver id, so it can not be started by a supplier, since this order requires driver to be started'], 403); 
            // }

            

            // check abrham samson
            // is the following condition required 
            // if ($orderUser->periodic === 1) { 
            //     return response()->json(['message' => 'this order is periodic. so the order needs a driver account to be started'], 403); 
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
                return response()->json(['message' => 'Order Update Failed'], 422);
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
                return response()->json(['message' => 'Vehicle Update Failed'], 422);
            }

            $updatedOrderUser = OrderUser::find($orderUser->id);
                
            return OrderUserForSupplierResource::make($updatedOrderUser->load('vehicleName', 'vehicle', 'driver'));

        });

        return $var;

    }



    /**
     * Update the specified resource in storage.
     * 
     * to Start Order = ORDER_STATUS_COMPLETE
     * 
     * when the vehicle is returned to the supplier
     */
    public function completeOrder(OrderUser $orderUser)
    {
        // AUTOMATIC : - here we will make the vehicle is_available = VEHICLE_AVAILABLE // order status = ORDER_STATUS_COMPLETE // and order end_date = today()

        $var = DB::transaction(function () use ($orderUser) {

            // get the supplier identity
            $user = auth()->user();
            $supplier = Supplier::find($user->id);

            if ($orderUser->supplier_id !== $supplier->id) {
                return response()->json(['message' => 'invalid Order is selected. Deceptive request Aborted.'], 403); 
            }


            if ($orderUser->status !== OrderUser::ORDER_STATUS_START) {
                return response()->json(['message' => 'this order is not STARTED. order should be STARTED before it can be COMPLETED.'], 403); 
            }

            // todays date
            $today = now()->format('Y-m-d');
            //
            // if "order status is set to complete"  // the order end_date must be set to  $today().
            // we do this Because if the order end date is in the future still and we sent ORDER_STATUS_COMPLETE, the project still charges the the customer for the remaining days until the project end_date is reached, even if the "order status is set to COPMPLETE"
            // solution is the above, if we make the order end date = today(), when order is set to Complete , then the order end_date will match the order Complete status,  and there will not be any left over dates we ask payment to after the order is complete
            //
            //
            $success = $orderUser->update([
                'status' => OrderUser::ORDER_STATUS_COMPLETE,
                'end_date' => $today,                           /* // if "order status is set to complete"  // the order end_date must be set to  $today() */
            ]);
            //
            if (!$success) {
                return response()->json(['message' => 'Order Update Failed'], 422);
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
                return response()->json(['message' => 'Vehicle Update Failed'], 422);
            }

            $updatedOrderUser = OrderUser::find($orderUser->id);
                
            return OrderUserForSupplierResource::make($updatedOrderUser->load('vehicleName', 'vehicle', 'driver'));

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
