<?php

namespace App\Http\Controllers\Api\V1\Customer;

use Carbon\Carbon;
use App\Models\Bid;
use App\Models\Vehicle;
use App\Models\Constant;
use App\Models\Customer;
use App\Models\OrderUser;
use App\Models\InvoiceUser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerRequests\AcceptBidRequest;
use App\Services\Api\V1\Customer\Payment\BOA\BOACustomerPaymentService;
use App\Http\Resources\Api\V1\OrderUserResources\OrderUserForCustomerResource;
use App\Services\Api\V1\Customer\Payment\TeleBirr\TeleBirrCustomerPaymentService;

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
            // get the customer identity
            $user = auth()->user();
            $customer = Customer::find($user->id);


            if (!$bid->orderUser) {
                return response()->json(['message' => 'The Parent Order of this Bid does NOT Exist.'], 404);
            }

            if ($customer->id != $bid->orderUser->customer_id) {
                return response()->json(['message' => 'invalid Bid is selected or Requested. or the requested Bid is not found. Deceptive request Aborted.'], 403);
            }

            if ($bid->vehicle->vehicle_name_id !== $bid->orderUser->vehicle_name_id) {
                return response()->json(['message' => 'invalid Bid is selected. or The Selected Bid does not match the orders requirement (this bid vehicle vehicle_name_id is NOT equal to the order vehicle_name_id). Deceptive request Aborted.'], 422);
            }

            if ($bid->vehicle->is_available !== Vehicle::VEHICLE_AVAILABLE) {
                return response()->json(['message' => 'the selected vehicle is not currently available'], 409);
            }



            if ($customer->is_active != 1) {
                return response()->json(['message' => 'Forbidden: Deactivated Customer'], 428);
            }
            if ($customer->is_approved != 1) {
                return response()->json(['message' => 'Forbidden: NOT Approved Customer'], 401);
            }













            if ($bid->orderUser->end_date < today()->toDateString()) {
                return response()->json(['message' => 'this bid parent order is Expired already.'], 410);
            }

            if ($bid->orderUser->is_terminated !== 0) {
                return response()->json(['message' => 'this bid parent order is Terminated'], 410);
            }


            // MANDATORY - - - - start
            //
            // we know accepting bids (i.e. acceptBid()) involve PAYING initial Payment
            // so for a single vehicle that has been bided for multiple orders, WE can NOT allow multiple customers to pay for that single vehicle, that has been bided for multiple orders while Accepting bid (i.e. acceptBid()), multiple times
            // in other words there should NOT be payments for multiple orders if the bid selected contains that single particular vehicle for those multiple orders, only one of them should be allowed to pay using that vehicle in the bid and, DIS-ALLOW the rest (bid-acceptance or payment) using that particular vehicle
            // 
            // SINCE single vehicle can bid for multiple orders 
            // we are going to make sure that single vehicle is NOT Accepted (& paid for) by those multiple orders during acceptBid().  (i.e. ONLY ONE of those orders can accept (i.e. acceptBid()) that vehicle in the bids and pay)
            //

            // $orderUsers = OrderUser::where('vehicle_id', $bid->vehicle_id)->orWhere('status', OrderUser::ORDER_STATUS_SET)->orWhere('status', OrderUser::ORDER_STATUS_START)->get(); // this may NOT work
            $orderUsers = OrderUser::where('vehicle_id', $bid->vehicle_id)
                ->whereIn('status', [OrderUser::ORDER_STATUS_START])
                ->get();

            if (!$orderUsers->isEmpty()) {

                foreach ($orderUsers as $orderUser) {

                    // $invoiceUserInspect = InvoiceUser::where('order_user_id', $orderUser->id)->where('status', InvoiceUser::INVOICE_STATUS_PAID)->whereNotNull('paid_date')->exists();
                    $invoiceUserInspect = $orderUser->invoiceUsers()->where('status', InvoiceUser::INVOICE_STATUS_PAID)->whereNotNull('paid_date')->exists();

                    if ($invoiceUserInspect) {
                        return response()->json([
                            'message' => 'the VEHICLE in the selected bid was already PAID for and ACCEPTED by another customer for another Order. Another Customer already paid for this vehicle_id to accept another order. vehicle_id: ' . $bid->vehicle_id,
                            'vehicle_id' => $bid->vehicle_id,
                        ], 409);
                    }
                }
            }
            //
            // MANDATORY - - - - end






            // check this scenario
            // if ($bid->orderUser->status !== OrderUser::ORDER_STATUS_PENDING) {
            //     return response()->json(['message' => 'this Bid Can Not be Selected. Because its order is not pending. it is already accepted , started or completed'], 409); 
            // }
            //


            // this MUST BE COMMENTED
            //
            // if (($bid->orderUser->vehicle_id !== null) || ($bid->orderUser->driver_id !== null) || ($bid->orderUser->supplier_id !== null)) {
            //     return response()->json(['message' => 'this bid can not be selected. Because its order is already being accepted and it already have a value on the columns (driver_id or supplier_id or vehicle_id) , for some reason'], 409); 
            // }


            // INSTEAD CHECK THIS
            $invoiceUserCheck = InvoiceUser::where('order_user_id', $bid->orderUser->id)->where('status', InvoiceUser::INVOICE_STATUS_PAID)->whereNotNull('paid_date')->exists();
            if ($invoiceUserCheck) {
                return response()->json(['message' => 'this Bid Can Not be Selected. the bidding step for this order is over. no more bidding is allowed. Because its parent order is already Accepted and PAID for.'], 409);
            }


            // if ($bid->vehicle->with_driver !== $bid->orderUser->with_driver) {

            //     if (($bid->vehicle->with_driver === 1) && ($bid->orderUser->with_driver === 0)) {
            //         return response()->json(['message' => 'the bid can not be selected. Because the parent order does not need a driver and the vehicle in the bid sends the vehicle with driver'], 422);
            //     } 
                /* else */ if (($bid->vehicle->with_driver === 0) && ($bid->orderUser->with_driver === 1)) {
                    return response()->json(['message' => 'the bid can not be selected. Because the order needs vehicle with a driver and the vehicle in the bid does not provide a driver'], 422);
                }


            //     return response()->json(['message' => 'the vehicle with_driver value is not equal with that of the order requirement.'], 422);
            // }

            // this if is important and should be right here 
            // this if should NOT be nested in any other if condition // this if should be independent and done just like this  // this if should be checked independently just like i did it right here
            if (($bid->vehicle->driver_id === null) && ($bid->orderUser->with_driver === 1)) {
                return response()->json(['message' => 'The bid can not be selected. Because the vehicle in the bid does not have actual driver currently. This Order Needs Vehicle that have Driver'], 422);
            }


            // get the order id of the selected bid
            $bidOrderId = $bid->orderUser->id;

            if (InvoiceUser::where('order_user_id', $bid->orderUser->id)->where('status', InvoiceUser::INVOICE_STATUS_NOT_PAID)->where('paid_date', null)->exists()) {
                // remove all the previous unpaid invoices for that order
                $successForceDelete = InvoiceUser::where('order_user_id', $bid->orderUser->id)->where('status', InvoiceUser::INVOICE_STATUS_NOT_PAID)->where('paid_date', null)->forceDelete();
                //
                if (!$successForceDelete) {
                    return response()->json(['message' => 'Failed to DELETE Useless invoices'], 500);
                }
            }


            // calculate the price_vehicle_payment.
            //      // calculate the vehicle payment PERCENT of the the DAILY_price_vehicle_payment portion for the parent order of this accepted bid. because PR asking is done using daily price , we need to make it suitable
            //
            //      // since the $bid->price_total is entered as DAILY price, we must calculate and get the 'price_vehicle_payment' portion from that daily price (i.e. $bid->price_total)
            //
            // orderUser end_date // from the order_users table
            $orderUserEndDate = Carbon::parse($orderUser->end_date); // because we need this for calculation we removed the toDateString   
            // orderUser start_date // from the order_users table 
            $orderUserStartDate = Carbon::parse($orderUser->start_date); // because we need this for calculation we removed the toDateString

            // the diffInDays method in Carbon accurately calculates the difference in days between two dates, considering the specific dates provided, including the actual number of days in each month and leap years. 
            // It does not assume all months have a fixed number of days like 30 days.
            $differenceInDays = $orderUserEndDate->diffInDays($orderUserStartDate);

            // this means = (DATE DIFFERENCE + 1) // because the order start_date is entitled for payment also
            $differenceInDaysPlusStartDate = $differenceInDays + 1;                
            //
            //
            $bid_DailyPrice_For_OrderUser = (int) $bid->price_total;     // this is daily price

            $bid_TotalPrice_For_OrderUser = $bid_DailyPrice_For_OrderUser * $differenceInDaysPlusStartDate;

            $constant = Constant::where('title', Constant::ORDER_USER_VEHICLE_PAYMENT_PERCENT)->first();
            //
            if (!$constant) {
                return response()->json(['message' => 'payment percent for the vehicle is not found.  ORDER_USER_VEHICLE_PAYMENT_PERCENT from constants table does not exist'], 404);
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
            $orderUserVehiclePaymentPercentConstant = $constant->percent_value;
            $vehiclePaymentMultiplierConstant = ((int) $orderUserVehiclePaymentPercentConstant) / 100;

            $dailyPortionOfVehiclePaymentPrice_CalculatedFromBid = $bid_DailyPrice_For_OrderUser * $vehiclePaymentMultiplierConstant;




            $withDriver = $bid->orderUser->with_driver;
            //
            $driverId = $withDriver === 1 ? $bid->vehicle->driver_id : null;
            //
            //
            $success = $bid->orderUser()->update([
                'vehicle_id' => $bid->vehicle_id,
                'driver_id' => $driverId,
                'supplier_id' => $bid->vehicle->supplier_id,
                'price_total' => $bid_TotalPrice_For_OrderUser, // this one is stored as PRICE TOTAL of all days of the order duration
                'price_vehicle_payment' => $dailyPortionOfVehiclePaymentPrice_CalculatedFromBid, // this one is stored as daily price
            ]);
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 500);
            }




            // generate Unique UUID for each individual Customer invoices
            $uuidTransactionIdSystem = Str::uuid(); // this uuid should be generated to be NEW and UNIQUE uuid (i.e. transaction_id_system) for Each invoice

            // create invoice for this order
            $invoiceUser = InvoiceUser::create([
                'order_user_id' => $bidOrderId,
                'transaction_id_system' => $uuidTransactionIdSystem,

                'price' => $bid->price_initial,
                'status' => InvoiceUser::INVOICE_STATUS_NOT_PAID,
                'paid_date' => null,                           // is NULL when the invoice is created initially, // and set when the invoice is paid by the organization
                'payment_method' => $request['payment_method'],
            ]);
            //
            if (!$invoiceUser) {
                return response()->json(['message' => 'Invoice Create Failed'], 500);
            }



            // get the updated order
            // $updatedOrderUser = OrderUser::find($bidOrderId);

            // get the newly created invoice
            $invoiceUserCreated = InvoiceUser::find($invoiceUser->id);
            // get the new invoice id
            $invoiceUserCreatedId = $invoiceUserCreated->id;
            // get the new invoice price
            $invoiceUserCreatedAmount = $invoiceUserCreated->price;



            /* START Payment Service Call */

            // do the actual payment 

            if ($request['payment_method'] == InvoiceUser::INVOICE_BOA) {

                // Setting values
                $boaCustomerPaymentService = new BOACustomerPaymentService($invoiceUserCreatedId);

                // Calling a non static method
                $valuePaymentRenderedView = $boaCustomerPaymentService->initiateInitialPaymentForVehicle();

                return $valuePaymentRenderedView;
            } else if ($request['payment_method'] == InvoiceUser::INVOICE_TELE_BIRR) {

                // Setting values
                $teleBirrCustomerPaymentService = new TeleBirrCustomerPaymentService();

                // Calling a non static method
                $valuePaymentRenderedView = $teleBirrCustomerPaymentService->initiateInitialPaymentForVehicle($invoiceUserCreatedId, $invoiceUserCreatedAmount);

                return $valuePaymentRenderedView;
            } else {
                return response()->json(['error' => 'Invalid payment method selected.'], 422);
            }


            /* END Payment Service Call */








            // WRONG RETURN FOR BOA payment
            // return response()->json(
            //     [
            //         'payment_link' => $boaPaymentService,
            //         'data' => OrderUserForCustomerResource::make($updatedOrderUser->load('vehicleName', 'vehicle', 'driver', 'bids', 'invoiceUsers')),
            //     ],
            //     200
            // );


        });

        return $var;
    }
    public function acceptBidNew(Bid $bid, int  $id, String $paymentMethod)
    {
        //
        $var = DB::transaction(function () use ($paymentMethod, $id, $bid) {
            // get the customer identity
            // $user = auth()->user();
            $customer = Customer::find($id);


            if (!$bid->orderUser) {
                return response()->json(['message' => 'The Parent Order of this Bid does NOT Exist.'], 404);
            }

            if ($customer->id != $bid->orderUser->customer_id) {
                return response()->json(['message' => 'invalid Bid is selected or Requested. or the requested Bid is not found. Deceptive request Aborted.'], 403);
            }

            if ($bid->vehicle->vehicle_name_id !== $bid->orderUser->vehicle_name_id) {
                return response()->json(['message' => 'invalid Bid is selected. or The Selected Bid does not match the orders requirement (this bid vehicle vehicle_name_id is NOT equal to the order vehicle_name_id). Deceptive request Aborted.'], 422);
            }

            if ($bid->vehicle->is_available !== Vehicle::VEHICLE_AVAILABLE) {
                return response()->json(['message' => 'the selected vehicle is not currently available'], 409);
            }



            if ($customer->is_active != 1) {
                return response()->json(['message' => 'Forbidden: Deactivated Customer'], 428);
            }
            if ($customer->is_approved != 1) {
                return response()->json(['message' => 'Forbidden: NOT Approved Customer'], 401);
            }













            if ($bid->orderUser->end_date < today()->toDateString()) {
                return response()->json(['message' => 'this bid parent order is Expired already.'], 410);
            }

            if ($bid->orderUser->is_terminated !== 0) {
                return response()->json(['message' => 'this bid parent order is Terminated'], 410);
            }


            // MANDATORY - - - - start
            //
            // we know accepting bids (i.e. acceptBid()) involve PAYING initial Payment
            // so for a single vehicle that has been bided for multiple orders, WE can NOT allow multiple customers to pay for that single vehicle, that has been bided for multiple orders while Accepting bid (i.e. acceptBid()), multiple times
            // in other words there should NOT be payments for multiple orders if the bid selected contains that single particular vehicle for those multiple orders, only one of them should be allowed to pay using that vehicle in the bid and, DIS-ALLOW the rest (bid-acceptance or payment) using that particular vehicle
            // 
            // SINCE single vehicle can bid for multiple orders 
            // we are going to make sure that single vehicle is NOT Accepted (& paid for) by those multiple orders during acceptBid().  (i.e. ONLY ONE of those orders can accept (i.e. acceptBid()) that vehicle in the bids and pay)
            //

            // $orderUsers = OrderUser::where('vehicle_id', $bid->vehicle_id)->orWhere('status', OrderUser::ORDER_STATUS_SET)->orWhere('status', OrderUser::ORDER_STATUS_START)->get(); // this may NOT work
            $orderUsers = OrderUser::where('vehicle_id', $bid->vehicle_id)
                ->whereIn('status', [OrderUser::ORDER_STATUS_START])
                ->get();

            if (!$orderUsers->isEmpty()) {

                foreach ($orderUsers as $orderUser) {

                    // $invoiceUserInspect = InvoiceUser::where('order_user_id', $orderUser->id)->where('status', InvoiceUser::INVOICE_STATUS_PAID)->whereNotNull('paid_date')->exists();
                    $invoiceUserInspect = $orderUser->invoiceUsers()->where('status', InvoiceUser::INVOICE_STATUS_PAID)->whereNotNull('paid_date')->exists();

                    if ($invoiceUserInspect) {
                        return response()->json([
                            'message' => 'the VEHICLE in the selected bid was already PAID for and ACCEPTED by another customer for another Order. Another Customer already paid for this vehicle_id to accept another order. vehicle_id: ' . $bid->vehicle_id,
                            'vehicle_id' => $bid->vehicle_id,
                        ], 409);
                    }
                }
            }
            //
            // MANDATORY - - - - end






            // check this scenario
            // if ($bid->orderUser->status !== OrderUser::ORDER_STATUS_PENDING) {
            //     return response()->json(['message' => 'this Bid Can Not be Selected. Because its order is not pending. it is already accepted , started or completed'], 409); 
            // }
            //


            // this MUST BE COMMENTED
            //
            // if (($bid->orderUser->vehicle_id !== null) || ($bid->orderUser->driver_id !== null) || ($bid->orderUser->supplier_id !== null)) {
            //     return response()->json(['message' => 'this bid can not be selected. Because its order is already being accepted and it already have a value on the columns (driver_id or supplier_id or vehicle_id) , for some reason'], 409); 
            // }


            // INSTEAD CHECK THIS
            $invoiceUserCheck = InvoiceUser::where('order_user_id', $bid->orderUser->id)->where('status', InvoiceUser::INVOICE_STATUS_PAID)->whereNotNull('paid_date')->exists();
            if ($invoiceUserCheck) {
                return response()->json(['message' => 'this Bid Can Not be Selected. the bidding step for this order is over. no more bidding is allowed. Because its parent order is already Accepted and PAID for.'], 409);
            }


            // if ($bid->vehicle->with_driver !== $bid->orderUser->with_driver) {

            //     if (($bid->vehicle->with_driver === 1) && ($bid->orderUser->with_driver === 0)) {
            //         return response()->json(['message' => 'the bid can not be selected. Because the parent order does not need a driver and the vehicle in the bid sends the vehicle with driver'], 422);
            //     } 
                /* else */ if (($bid->vehicle->with_driver === 0) && ($bid->orderUser->with_driver === 1)) {
                    return response()->json(['message' => 'the bid can not be selected. Because the order needs vehicle with a driver and the vehicle in the bid does not provide a driver'], 422);
                }


            //     return response()->json(['message' => 'the vehicle with_driver value is not equal with that of the order requirement.'], 422);
            // }

            // this if is important and should be right here 
            // this if should NOT be nested in any other if condition // this if should be independent and done just like this  // this if should be checked independently just like i did it right here
            if (($bid->vehicle->driver_id === null) && ($bid->orderUser->with_driver === 1)) {
                return response()->json(['message' => 'The bid can not be selected. Because the vehicle in the bid does not have actual driver currently. This Order Needs Vehicle that have Driver'], 422);
            }


            // get the order id of the selected bid
            $bidOrderId = $bid->orderUser->id;

            if (InvoiceUser::where('order_user_id', $bid->orderUser->id)->where('status', InvoiceUser::INVOICE_STATUS_NOT_PAID)->where('paid_date', null)->exists()) {
                // remove all the previous unpaid invoices for that order
                $successForceDelete = InvoiceUser::where('order_user_id', $bid->orderUser->id)->where('status', InvoiceUser::INVOICE_STATUS_NOT_PAID)->where('paid_date', null)->forceDelete();
                //
                if (!$successForceDelete) {
                    return response()->json(['message' => 'Failed to DELETE Useless invoices'], 500);
                }
            }


            // calculate the price_vehicle_payment.
            //      // calculate the vehicle payment PERCENT of the the DAILY_price_vehicle_payment portion for the parent order of this accepted bid. because PR asking is done using daily price , we need to make it suitable
            //
            //      // since the $bid->price_total is entered as DAILY price, we must calculate and get the 'price_vehicle_payment' portion from that daily price (i.e. $bid->price_total)
            //
            // orderUser end_date // from the order_users table
            $orderUserEndDate = Carbon::parse($orderUser->end_date); // because we need this for calculation we removed the toDateString   
            // orderUser start_date // from the order_users table 
            $orderUserStartDate = Carbon::parse($orderUser->start_date); // because we need this for calculation we removed the toDateString

            // the diffInDays method in Carbon accurately calculates the difference in days between two dates, considering the specific dates provided, including the actual number of days in each month and leap years. 
            // It does not assume all months have a fixed number of days like 30 days.
            $differenceInDays = $orderUserEndDate->diffInDays($orderUserStartDate);

            // this means = (DATE DIFFERENCE + 1) // because the order start_date is entitled for payment also
            $differenceInDaysPlusStartDate = $differenceInDays + 1;                
            //
            //
            $bid_DailyPrice_For_OrderUser = (int) $bid->price_total;     // this is daily price

            $bid_TotalPrice_For_OrderUser = $bid_DailyPrice_For_OrderUser * $differenceInDaysPlusStartDate;

            $constant = Constant::where('title', Constant::ORDER_USER_VEHICLE_PAYMENT_PERCENT)->first();
            //
            if (!$constant) {
                return response()->json(['message' => 'payment percent for the vehicle is not found.  ORDER_USER_VEHICLE_PAYMENT_PERCENT from constants table does not exist'], 404);
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
            $orderUserVehiclePaymentPercentConstant = $constant->percent_value;
            $vehiclePaymentMultiplierConstant = ((int) $orderUserVehiclePaymentPercentConstant) / 100;

            $dailyPortionOfVehiclePaymentPrice_CalculatedFromBid = $bid_DailyPrice_For_OrderUser * $vehiclePaymentMultiplierConstant;




            $withDriver = $bid->orderUser->with_driver;
            //
            $driverId = $withDriver === 1 ? $bid->vehicle->driver_id : null;
            //
            //
            $success = $bid->orderUser()->update([
                'vehicle_id' => $bid->vehicle_id,
                'driver_id' => $driverId,
                'supplier_id' => $bid->vehicle->supplier_id,
                'price_total' => $bid_TotalPrice_For_OrderUser, // this one is stored as PRICE TOTAL of all days of the order duration
                'price_vehicle_payment' => $dailyPortionOfVehiclePaymentPrice_CalculatedFromBid, // this one is stored as daily price
            ]);
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 500);
            }




            // generate Unique UUID for each individual Customer invoices
            $uuidTransactionIdSystem = Str::uuid(); // this uuid should be generated to be NEW and UNIQUE uuid (i.e. transaction_id_system) for Each invoice

            // create invoice for this order
            $invoiceUser = InvoiceUser::create([
                'order_user_id' => $bidOrderId,
                'transaction_id_system' => $uuidTransactionIdSystem,

                'price' => $bid->price_initial,
                'status' => InvoiceUser::INVOICE_STATUS_NOT_PAID,
                'paid_date' => null,                           // is NULL when the invoice is created initially, // and set when the invoice is paid by the organization
                'payment_method' => $paymentMethod,
            ]);
            //
            if (!$invoiceUser) {
                return response()->json(['message' => 'Invoice Create Failed'], 500);
            }



            // get the updated order
            // $updatedOrderUser = OrderUser::find($bidOrderId);

            // get the newly created invoice
            $invoiceUserCreated = InvoiceUser::find($invoiceUser->id);
            // get the new invoice id
            $invoiceUserCreatedId = $invoiceUserCreated->id;
            // get the new invoice price
            $invoiceUserCreatedAmount = $invoiceUserCreated->price;



            /* START Payment Service Call */

            // do the actual payment 

            if ($paymentMethod == InvoiceUser::INVOICE_BOA) {

                // Setting values
                $boaCustomerPaymentService = new BOACustomerPaymentService($invoiceUserCreatedId);

                // Calling a non static method
                $valuePaymentRenderedView = $boaCustomerPaymentService->initiateInitialPaymentForVehicle();

                return $valuePaymentRenderedView;
            } else if ($paymentMethod == InvoiceUser::INVOICE_TELE_BIRR) {

                // Setting values
                $teleBirrCustomerPaymentService = new TeleBirrCustomerPaymentService();

                // Calling a non static method
                $valuePaymentRenderedView = $teleBirrCustomerPaymentService->initiateInitialPaymentForVehicle($invoiceUserCreatedId, $invoiceUserCreatedAmount);

                return $valuePaymentRenderedView;
            } else {
                return response()->json(['error' => 'Invalid payment method selected.'], 422);
            }


            /* END Payment Service Call */








            // WRONG RETURN FOR BOA payment
            // return response()->json(
            //     [
            //         'payment_link' => $boaPaymentService,
            //         'data' => OrderUserForCustomerResource::make($updatedOrderUser->load('vehicleName', 'vehicle', 'driver', 'bids', 'invoiceUsers')),
            //     ],
            //     200
            // );


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
