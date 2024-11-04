<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Bid;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Constant;
use App\Models\Supplier;
use App\Models\OrderUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BidResources\BidResource;
use App\Http\Requests\Api\V1\AdminRequests\StoreBidRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateBidRequest;


class BidController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Bid::class);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBidRequest $request)
    {
        // do AUTH here or in the controller
        
        $var = DB::transaction(function () use ($request) {

            $vehicle = Vehicle::find($request['vehicle_id']);
            $supplier = Supplier::find($vehicle->supplier_id);  // i could use relation, instead of fetching all ,  =     $vehicle->supplier->is_active   and     $vehicle->supplier->is_approved         // check abrham samson
            $driver = Driver::find($vehicle->driver_id);        // i could use relation, instead of fetching all ,  =     $vehicle->driver->is_active     and     $vehicle->supplier->is_approved         // check abrham samson
            $orderUser = OrderUser::find($request['order_user_id']);

            
            if ($vehicle->vehicle_name_id !== $orderUser->vehicle_name_id) {
                return response()->json(['message' => 'invalid Vehicle is selected. or The Selected Vehicle does not match the orders requirement (the selected vehicle vehicle_name_id is NOT equal to the order vehicle_name_id). Deceptive request Aborted.'], 422); 
            }

            if (Bid::where('vehicle_id', $vehicle->id)->where('order_user_id', $orderUser->id)->exists()) {
                return response()->json(['message' => 'you already bid for this order with this vehicle'], 409); 
            }

            if ($vehicle->is_available !== Vehicle::VEHICLE_AVAILABLE) {
                return response()->json(['message' => 'the selected vehicle is not currently available'], 409); 
            }


            // i could use relation, instead of fetching all ,  =     $vehicle->driver->is_active     and     $vehicle->supplier->is_approved         // check abrham samson
            if ($driver) {
                if ($driver->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Driver'], 428); 
                }
                if ($driver->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Driver'], 428); 
                }
            }
            // i could use relation, instead of fetching all ,  =     $vehicle->supplier->is_active   and     $vehicle->supplier->is_approved         // check abrham samson
            if ($supplier) {
                if ($supplier->is_active != 1) {
                    return response()->json(['message' => 'Forbidden: Deactivated Supplier'], 428); 
                }
                if ($supplier->is_approved != 1) {
                    return response()->json(['message' => 'Forbidden: NOT Approved Supplier'], 428); 
                }
            }


            








            if ($orderUser->status !== OrderUser::ORDER_STATUS_PENDING) {
                return response()->json(['message' => 'this order is not pending. it is already accepted , started or completed'], 409); 
            }

            if ($orderUser->end_date < today()->toDateString()) {
                return response()->json(['message' => 'this order is Expired already.'], 410); 
            }

            if ($orderUser->is_terminated !== 0) {
                return response()->json(['message' => 'this order is Terminated'], 410); 
            }
            
            if (($orderUser->vehicle_id !== null) || ($orderUser->driver_id !== null) || ($orderUser->supplier_id !== null)) {
                return response()->json(['message' => 'this order is already being accepted and it already have a value on the columns (driver_id or supplier_id or vehicle_id) , for some reason'], 409); 
            }


            
            

            if ($vehicle->with_driver !== $orderUser->with_driver) {

                if (($vehicle->with_driver === 1) && ($orderUser->with_driver === 0)) {
                    return response()->json(['message' => 'the order does not need a driver'], 422); 
                }
                else if (($vehicle->with_driver === 0) && ($orderUser->with_driver === 1)) {
                    return response()->json(['message' => 'the order needs vehicle with a driver'], 422); 
                }
                

                return response()->json(['message' => 'the vehicle with_driver value is not equal with that of the order requirement.'], 422); 
                
            }

            // this if is important and should be right here 
            // this if should NOT be nested in any other if condition // this if should be independent and done just like this  // this if should be checked independently just like i did it right here
            if (($vehicle->driver_id === null) && ($orderUser->with_driver === 1)) {
                return response()->json(['message' => 'the vehicle you selected for the order does not have actual driver currently. This Order Needs Vehicle that have Driver'], 422); 
            }
            

            // calculate the initial payment for this bid entry
            $priceTotalFromRequest = (int) $request['price_total'];
            
            $constant = Constant::where('title', Constant::ORDER_USER_INITIAL_PAYMENT_PERCENT)->first();
            //
            if (!$constant) {
                return response()->json(['message' => 'initial payment percent for individual customers orders is not found.  ORDER_USER_INITIAL_PAYMENT_PERCENT from constants table does not exist'], 404); 
            }
            // check if $constant->percent_value is NULL
            if ($constant->percent_value === null) {
                return response()->json([
                    'message' => 'Invalid percent value retrieved from the constants table. The percent value can not be null.'
                ], 422);
            }
            // Check if the percent value is within the valid range
            if ($constant->percent_value < 1 || $constant->percent_value > 100) {
                return response()->json([
                    'message' => 'Invalid percent value retrieved from the constants table. The percent value must be between 1 and 100.'
                ], 422);
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
                return response()->json(['message' => 'Bid Create Failed'], 500);
            }


            $bidValue = Bid::find($bid->id);


            return BidResource::make($bidValue->load('orderUser'));
                 
        });

        return $var;
    }


    /**
     * Display the specified resource.
     */
    public function show(Bid $bid)
    {
        $this->authorize('view', $bid);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBidRequest $request, Bid $bid)
    {
        //
        $var = DB::transaction(function () use($request, $bid) {

            $priceTotal = $bid->price_total;
            $priceInitial = $bid->price_initial;

            if ($request->has('price_total') && isset($request['price_total'])) {
                
                // calculate the initial payment for this bid entry
                $priceTotalFromRequest = (int) $request['price_total'];
                
                $constant = Constant::where('title', Constant::ORDER_USER_INITIAL_PAYMENT_PERCENT)->first();
                //
                if (!$constant) {
                    return response()->json(['message' => 'initial payment percent for individual customers orders is not found.  ORDER_USER_INITIAL_PAYMENT_PERCENT from constants table does not exist'], 404); 
                }
                // check if $constant->percent_value is NULL
                if ($constant->percent_value === null) {
                    return response()->json([
                        'message' => 'Invalid percent value retrieved from the constants table. The percent value can not be null.'
                    ], 422);
                }
                // Check if the percent value is within the valid range
                if ($constant->percent_value < 1 || $constant->percent_value > 100) {
                    return response()->json([
                        'message' => 'Invalid percent value retrieved from the constants table. The percent value must be between 1 and 100.'
                    ], 422);
                }
                $orderUserInitialPaymentPercentConstant = $constant->percent_value;
                $initialPaymentMultiplierConstant = ((int) $orderUserInitialPaymentPercentConstant)/100;

                // set initial price
                $priceInitial = $priceTotalFromRequest * $initialPaymentMultiplierConstant;

                // set total price
                $priceTotal = (int) $request['price_total'];

            }


            $success = $bid->update([
                'price_total' => $priceTotal,
                'price_initial' => $priceInitial,
            ]);
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 500);
            }
            
            $updatedBid = Bid::find($bid->id);

            return BidResource::make($updatedBid->load('orderUser'));
        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bid $bid)
    {
        $this->authorize('delete', $bid);

        $var = DB::transaction(function () use ($bid) {

            $bid->delete();

            return response()->json(true, 200);

        });

        return $var;
    }


    public function restore(string $id)
    {
        $bid = Bid::withTrashed()->find($id);

        $this->authorize('restore', $bid);

        $var = DB::transaction(function () use ($bid) {
            
            if (!$bid) {
                abort(404);    
            }
    
            $bid->restore();
    
            return response()->json(true, 200);

        });

        return $var;
        
    }


}
