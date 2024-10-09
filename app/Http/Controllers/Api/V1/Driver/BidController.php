<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Models\Bid;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Constant;
use App\Models\OrderUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DriverRequests\StoreBidRequest;
use App\Http\Resources\Api\V1\BidResources\BidForDriverResource;

class BidController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBidRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {
            // get the driver identity
            $user = auth()->user();
            $driver = Driver::find($user->id);

            $vehicle = Vehicle::find($request['vehicle_id']);
            $orderUser = OrderUser::find($request['order_user_id']);


            if ($vehicle->driver_id !== $driver->id) {
                return response()->json(['message' => 'invalid Vehicle is selected. or This Driver does not have a vehicle with this id. Deceptive request Aborted.'], 403); 
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



            if ($driver->is_active != 1) {
                return response()->json(['message' => 'Forbidden: Deactivated Driver'], 403); 
            }
            if ($driver->is_approved != 1) {
                return response()->json(['message' => 'Forbidden: NOT Approved Driver'], 403); 
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
                'order_user_id' => $request['order_user_id'],
                'vehicle_id' => $request['vehicle_id'],

                'price_total' => $request['price_total'],
                'price_initial' => $priceInitial,
            ]);
            //
            if (!$bid) {
                return response()->json(['message' => 'Bid Create Failed'], 422);
            }


            $bidValue = Bid::find($bid->id);


            return BidForDriverResource::make($bidValue->load('orderUser'));
                 
        });

        return $var;
    }


    

    /**
     * Display the specified resource.
     */
    public function show(Bid $bid)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bid $bid)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bid $bid)
    {
        //
    }
}
