<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\Bid;
use App\Models\Vehicle;
use App\Models\Customer;
use App\Models\OrderUser;
use App\Models\InvoiceUser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\Customer\PrPaymentService;
use App\Http\Requests\Api\V1\CustomerRequests\AcceptBidRequest;
use App\Http\Resources\Api\V1\OrderUserResources\OrderUserForCustomerResource;

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
    public function show(Bid $bid)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     */
    public function acceptBid(AcceptBidRequest $request, Bid $bid)
    {
        //
        $var = DB::transaction(function () use ($request, $bid) {
            // get the supplier identity
            $user = auth()->user();
            $customer = Customer::find($user->id);

            
            if (!$bid->orderUser) {
                return response()->json(['message' => 'The Parent Order of this Bid does NOT Exist.'], 404);
            }

            if ($customer->id != $bid->orderUser->customer_id) {
                return response()->json(['message' => 'invalid Bid is selected or Requested. or the requested Bid is not found. Deceptive request Aborted.'], 401);
            }

            if ($bid->vehicle->vehicle_name_id !== $bid->orderUser->vehicle_name_id) {
                return response()->json(['message' => 'invalid Bid is selected. or The Selected Bid does not match the orders requirement (this bid vehicle vehicle_name_id is NOT equal to the order vehicle_name_id). Deceptive request Aborted.'], 403); 
            }

            if ($bid->vehicle->is_available !== Vehicle::VEHICLE_AVAILABLE) {
                return response()->json(['message' => 'the selected vehicle is not currently available'], 403); 
            }



            if ($customer->is_active != 1) {
                return response()->json(['message' => 'Forbidden: Deactivated Customer'], 403); 
            }
            if ($customer->is_approved != 1) {
                return response()->json(['message' => 'Forbidden: NOT Approved Customer'], 403); 
            }











            if ($bid->orderUser->status !== OrderUser::ORDER_STATUS_PENDING) {
                return response()->json(['message' => 'this Bid Can Not be Selected. Because its order is not pending. it is already accepted , started or completed'], 403); 
            }

            if ($bid->orderUser->end_date < today()->toDateString()) {
                return response()->json(['message' => 'this bid parent order is Expired already.'], 403); 
            }

            if ($bid->orderUser->is_terminated !== 0) {
                return response()->json(['message' => 'this bid parent order is Terminated'], 403); 
            }
            
            // this is commented because of samson // check abrham samson
            // if (($bid->orderUser->vehicle_id !== null) || ($bid->orderUser->driver_id !== null) || ($bid->orderUser->supplier_id !== null)) {
            //     return response()->json(['message' => 'this bid can not be selected. Because its order is already being accepted and it already have a value on the columns (driver_id or supplier_id or vehicle_id) , for some reason'], 403); 
            // }


            if ($bid->vehicle->with_driver !== $bid->orderUser->with_driver) {

                if (($bid->vehicle->with_driver === 1) && ($bid->orderUser->with_driver === 0)) {
                    return response()->json(['message' => 'the bid can not be selected. Because the parent order does not need a driver and the vehicle in the bid sends the vehicle with driver'], 403); 
                }
                else if (($bid->vehicle->with_driver === 0) && ($bid->orderUser->with_driver === 1)) {
                    return response()->json(['message' => 'the bid can not be selected. Because the order needs vehicle with a driver and the vehicle in the bid does not provide a driver'], 403); 
                }
                

                return response()->json(['message' => 'the vehicle with_driver value is not equal with that of the order requirement.'], 403); 
                
            }

            // this if is important and should be right here 
            // this if should NOT be nested in any other if condition // this if should be independent and done just like this  // this if should be checked independently just like i did it right here
            if (($bid->vehicle->driver_id === null) && ($bid->orderUser->with_driver === 1)) {
                return response()->json(['message' => 'The bid can not be selected. Because the vehicle in the bid does not have actual driver currently. This Order Needs Vehicle that have Driver'], 403); 
            }
            

            
            $withDriver = $bid->orderUser->with_driver;
            //
            $driverId = $withDriver === 1 ? $bid->vehicle->driver_id : null;
            //
            //
            $success = $bid->orderUser()->update([
                'vehicle_id' => $bid->vehicle_id,
                'driver_id' => $driverId,
                'supplier_id' => $bid->vehicle->supplier_id,
                'price_total' => $bid->price_total,
                
            ]);
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 422);
            }

            // get the order id of the selected bid
            $bidOrderId = $bid->orderUser->id;
            
            // remove all the previous upaid invoices for that order
            InvoiceUser::where('order_user_id', $bid->orderUser->id)->where('status', InvoiceUser::INVOICE_STATUS_NOT_PAID)->where('paid_date', null)->forceDelete();

            $uuidTransactionIdSystem = Str::uuid();

            // create invoice for this order
            $invoice = InvoiceUser::create([
                'order_user_id' => $bidOrderId,
                'transaction_id_system' => $uuidTransactionIdSystem,

                'price' => $bid->price_initial,
                'status' => InvoiceUser::INVOICE_STATUS_NOT_PAID,
                'paid_date' => null,                           // is NULL when the invoice is created initially, // and set when the invoice is paid by the organization
                'payment_method' => $request['payment_method'],
            ]);
            //
            if (!$invoice) {
                return response()->json(['message' => 'Invoice Create Failed'], 422);
            }

            

            // get the updated order
            $updatedOrderUser = OrderUser::find($bidOrderId);

            // get the newly created invoice
            $invoiceCreated = InvoiceUser::find($invoice->id);
            // get the new invoice id
            $invoiceCreatedId = $invoiceCreated->id;




            /* START Payment Service Call */
            
            // do the actual payment 
            $priceInitialOfAcceptedBid = $bid->price_initial;
            // pass the $priceInitialOfAcceptedBid to be paid   and   pass the $invoiceCreatedId so that it could be used in the callback endpoint to change the status of the paid invoices
            $valuePayment = PrPaymentService::payPrs($priceInitialOfAcceptedBid, $invoiceCreatedId);

            if ($valuePayment === false) {
                return response()->json(['message' => 'payment operation failed from the banks side'], 500);
            }


            /* END Payment Service Call */
                


            
            
            

            
            // return the data values needed
            return response()->json(
                [
                    'payment_link' => $valuePayment,
                    'data' => OrderUserForCustomerResource::make($updatedOrderUser->load('vehicleName', 'vehicle', 'driver', 'bids', 'invoiceUsers')),
                ],
                200
            );

                 
        });

        return $var;
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
