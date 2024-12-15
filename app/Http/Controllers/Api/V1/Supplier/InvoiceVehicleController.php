<?php

namespace App\Http\Controllers\Api\V1\Supplier;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\OrderUser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\InvoiceVehicle;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\SupplierRequests\StoreInvoiceVehicleOrderRequest;
use App\Http\Requests\Api\V1\SupplierRequests\UpdateInvoiceVehicleOrderRequest;
use App\Http\Requests\Api\V1\SupplierRequests\StoreInvoiceVehicleOrderUserRequest;
use App\Http\Requests\Api\V1\SupplierRequests\UpdateInvoiceVehicleOrderUserRequest;
use App\Http\Resources\Api\V1\InvoiceVehicleResources\InvoiceVehicleForSupplierResource;

class InvoiceVehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $request->validate([
            'invoice_status_search' => [
                'sometimes', 'string', Rule::in([InvoiceVehicle::INVOICE_STATUS_NOT_PAID, InvoiceVehicle::INVOICE_STATUS_PAID]),
            ],
            // Other validation rules if needed
        ]);

        $user = auth()->user();
        $supplier = Supplier::find($user->id);

        $invoiceVehicles = InvoiceVehicle::where('supplier_id', $supplier->id);

        // use Filtering service OR Scope to do this
        if ($request->has('order_id_search')) {
            if (isset($request['order_id_search'])) {
                $orderId = $request['order_id_search'];

                $invoiceVehicles = $invoiceVehicles->where('order_id', $orderId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('order_user_id_search')) {
            if (isset($request['order_user_id_search'])) {
                $orderUserId = $request['order_user_id_search'];

                $invoiceVehicles = $invoiceVehicles->where('order_user_id', $orderUserId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('invoice_status_search')) {
            if (isset($request['invoice_status_search'])) {
                $invoiceStatus = $request['invoice_status_search'];

                $invoiceVehicles = $invoiceVehicles->where('status', $invoiceStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }

        }

        

        $invoiceVehiclesData = $invoiceVehicles->with('order', 'orderUser')->latest()->paginate(FilteringService::getPaginate($request));

        return InvoiceVehicleForSupplierResource::collection($invoiceVehiclesData);

    }

    /**
     * Store a newly created resource in storage.
     * 
     * Supplier is asking Adiamat PR for his vehicles that are in Organizations order table
     */
    public function storeInvoiceVehicleForOrder(StoreInvoiceVehicleOrderRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {

            $user = auth()->user();
            $supplier = Supplier::find($user->id);

            $order = Order::find($request['order_id']);

            if ($order->supplier_id !== $supplier->id) {
                // this vehicle is NOT be owned by the logged in driver
                return response()->json(['message' => 'invalid Order is selected for Vehicle PR Request. or the requested Order is not found. Deceptive request Aborted.'], 403);
            }

            $uuidTransactionIdSystem = Str::uuid(); // this uuid should be generated OUTSIDE the FOREACH to Generate COMMON and SAME uuid (i.e. transaction_id_system) for ALL invoices that have similar invoice_code (or for all invoices created in one PR request)

            // todays date
            $today = now()->format('Y-m-d');


            if ($order->begin_date === null) {
                return response()->json(['message' => 'you can not ask PR for this Order. because this Order Begin Date is null, this order is not STARTED. order: ' . $order->id . ' , the order Begin Date must be set before asking PR for it.'], 428);
            }
            //
            // Check if the begin_date is a valid date
            if (strtotime($order->begin_date) === false) {
                return response()->json(['message' => 'The Order Begin Date is not a valid date.'], 400);
            }

            // lets check the order actual status
            if ($order->status === Order::ORDER_STATUS_PENDING) {
                return response()->json(['message' => 'you can not ask PR for an order with a status PENDING. order: ' . $order->id . ' , an order must have status START or COMPLETE to be eligible for PR asking.'], 422);
            }
            if ($order->status === Order::ORDER_STATUS_SET) {
                return response()->json(['message' => 'you can not ask PR for an order with a status SET. the order is only accepted and not started. order: ' . $order->id . ' , an order must have status START or COMPLETE to be eligible for PR asking.'], 422);
            }
            

            // lets check the order vehicle_pr_status
            if ($order->vehicle_pr_status === Order::VEHICLE_PR_LAST) {
                return response()->json(['message' => 'every Available PR request have been already asked for this order: ' . $order->id . ' , The order have PR_LAST status.'], 409);
            }
            if ($order->vehicle_pr_status === Order::VEHICLE_PR_COMPLETED) {
                return response()->json(['message' => 'all PR is paid for this order: ' . $order->id . ' , The order have PR_COMPLETED status.'], 409);
            }
            if ($order->vehicle_pr_status === Order::VEHICLE_PR_TERMINATED) {
                return response()->json(['message' => 'this order PR is terminated for some reason. please check with the system admin why it is terminated. order: ' . $order->id . ' , The order have PR_TERMINATED status.'], 410);
            }




            // invoice date // from the request
            $invoiceRequestEndDateValue = Carbon::parse($request['end_date'])->toDateString();
            // order end date // from orders table in the database
            $orderEndDate = Carbon::parse($order->end_date)->toDateString();
           

            if ($invoiceRequestEndDateValue >= $today) {
                return response()->json(['message' => 'invoice asking End date must be Less than Today.'], 400);
            }

            // invoice end_date from the request can NOT be greater than the order end_date. // but invoice end_date from the request can be EQUALs to the order end_date
            if ($invoiceRequestEndDateValue > $orderEndDate) {
                return response()->json(['message' => 'invoice end_date in the request can NOT be greater than the order end_date.    invoice asking end_date can NOT be after the date the order ends'], 400);
            }


            if ($order->vehicle_pr_status === null) {
                // this means the PR request we are making is for the first time (PR request made for FIRST time)
                // in this case we include the order begin_date to calculate the price
                // this means = (DATE DIFFERENCE + 1) // because the begin_date is entitled for payment also


                // order begin date // from orders table in the database
                $orderBeginDate = Carbon::parse($order->begin_date)->toDateString();
                
                // invoice end_date from the request can NOT be less than the order begin_date. // but invoice end_date from the request can be EQUALs to the order begin_date
                if ($invoiceRequestEndDateValue < $orderBeginDate) {
                    return response()->json(['message' => 'invoice asking end_date in the request can NOT be less than the order begin_date.    invoice asking end_date can NOT be before the date the order begins'], 400);
                }


            }
            else if ($order->vehicle_pr_status === Order::VEHICLE_PR_STARTED && InvoiceVehicle::where('order_id', $order->id)->exists()) {
                // this means the PR request we are making is NOT for the first time (PR request made for SUBSEQUENT time)
                // in this case we does NOT include the last invoice end_date to calculate the price BECAUSE that date is considered in price calculation in the last invoice
                // this means only = (DATE DIFFERENCE) // only the actual subtraction will be used


                $unPaidOrderInvoiceExist = InvoiceVehicle::where('order_id', $order->id)
                                ->where('status', InvoiceVehicle::INVOICE_STATUS_NOT_PAID)
                                ->exists();
                
                if ($unPaidOrderInvoiceExist) {
                    return response()->json([
                        'message' => 'there is NOT_PAID invoice in invoice_vehicles table with this order, you need to ask the system admins to pay the previous PR for this particular order before asking another PR for this particular Order: ' . $order->id,
                        'order_id' => $order->id,
                        'order_vehicle_plate_number' => $order->vehicle->plate_number
                    ], 428);
                }


                // lets get the last invoice asked with this order_id
                $lastInvoice = $order->invoiceVehicles()->latest()->first();

                if (!$lastInvoice) {
                    return response()->json(['message' => 'The last invoice asked for this order can not be found. This Invoice Can NOT be Processed'], 500);
                }


                // the last asked invoice end_date of this order // from invoices table in the database
                $lastInvoiceEndDate = Carbon::parse($lastInvoice->end_date)->toDateString();

                // invoice end_date from the request can NOT be Less than or Equals to the last invoice end_date. // invoice end_date from the request should always be greater than the last invoice end_date
                if ($invoiceRequestEndDateValue <= $lastInvoiceEndDate) {
                    return response()->json(['message' => 'invoice end_date in the request can NOT be Less than or Equal to the last asked invoice (end_date) of this order.    invoice end_date from the request should always be greater than the last asked invoice (end_date) of this order'], 422);
                }
                


            }
            else {
                return response()->json(['message' => 'This Invoice Can NOT be Processed'], 422);
            }



            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




            // invoice date // from the request
            $invoiceRequestEndDate = Carbon::parse($request['end_date']); // because we need this for calculation we removed the toDateString
            $invoiceRequestEndDateStringVersion = Carbon::parse($request['end_date'])->toDateString(); // this is used for comparison in the following if condition
            // order end date // from orders table in the database
            $orderEndDate = Carbon::parse($order->end_date)->toDateString();

            // get the daily price of the order vehicle_name_id from contract_details_table;
            $orderPricePerDay = $order->contractDetail->price_vehicle_payment;

            
            if ($invoiceRequestEndDateStringVersion < $orderEndDate) {
                $vehiclePrStatus = Order::VEHICLE_PR_STARTED;
            }
            if ($invoiceRequestEndDateStringVersion === $orderEndDate) {
                $vehiclePrStatus = Order::VEHICLE_PR_LAST;
            }
            

            
            if ($order->vehicle_pr_status === null) {
                // this means the PR request we are making is for the first time (PR request made for FIRST time)
                // in this case we include the order begin_date to calculate the price
                // this means = (DATE DIFFERENCE + 1) // because the begin_date is entitled for payment also

                $invoiceStartDate = Carbon::parse($order->begin_date); // because we need this for calculation we removed the toDateString

                // the diffInDays method in Carbon accurately calculates the difference in days between two dates, considering the specific dates provided, including the actual number of days in each month and leap years. 
                // It does not assume all months have a fixed number of days like 30 days.
                $differenceInDays = $invoiceRequestEndDate->diffInDays($invoiceStartDate);

                // this means = (DATE DIFFERENCE + 1) // because the order begin_date is entitled for payment also
                $differenceInDaysPlusBeginDate = $differenceInDays + 1;
                
                $invoicePriceAmountOfAllAskedDays = $differenceInDaysPlusBeginDate * $orderPricePerDay;



            }
            else if ($order->vehicle_pr_status === Order::VEHICLE_PR_STARTED && InvoiceVehicle::where('order_id', $order->id)->exists()) {
                // this means the PR request we are making is NOT for the first time (PR request made for SUBSEQUENT time)
                // in this case we does NOT include the last invoice end_date to calculate the price BECAUSE that date is considered in price calculation in the last invoice
                // this means only = (DATE DIFFERENCE) // only the actual subtraction will be used


                // lets get the last invoice asked with this order_id
                $lastInvoice = $order->invoiceVehicles()->latest()->first();

                if (!$lastInvoice) {
                    return response()->json(['message' => 'The last invoice asked for this order can not be found. This Invoice Can NOT be Processed'], 500);
                }


                // the last asked invoice end_date of this order // from invoices table in the database
                $invoiceStartDate = Carbon::parse($lastInvoice->end_date); // because we need this for calculation we removed the toDateString

                // the diffInDays method in Carbon accurately calculates the difference in days between two dates, considering the specific dates provided, including the actual number of days in each month and leap years. 
                // It does not assume all months have a fixed number of days like 30 days.
                $differenceInDays = $invoiceRequestEndDate->diffInDays($invoiceStartDate);

                $invoicePriceAmountOfAllAskedDays = $differenceInDays * $orderPricePerDay;


            }
            else {
                return response()->json(['message' => 'This Invoice Can NOT be Processed'], 422);
            }

            

            $invoiceVehicle = InvoiceVehicle::create([
                'order_id' => $request['order_id'],
                'supplier_id' => $order->supplier_id,

                'transaction_id_system' => $uuidTransactionIdSystem,

                'start_date' => $invoiceStartDate,
                'end_date' => $request['end_date'],

                'price_amount' => $invoicePriceAmountOfAllAskedDays,
                'status' => InvoiceVehicle::INVOICE_STATUS_NOT_PAID,
                'paid_date' => null,                           // is NULL when the invoice is created initially, // and set when the invoice is paid by the organization                                                                                  
            ]);
            //
            if (!$invoiceVehicle) {
                return response()->json(['message' => 'InvoiceVehicle Create Failed'], 500);
            }


            $success = $order->update([
                'vehicle_pr_status' => $vehiclePrStatus,
            ]);
            //
            if (!$success) {
                return response()->json(['message' => 'Order Update Failed'], 500);
            }



            return InvoiceVehicleForSupplierResource::make($invoiceVehicle->load('order', 'orderUser'));

            
        });

        return $var;
    }

    /**
     * Store a newly created resource in storage.
     * 
     * Supplier is asking Adiamat PR for his vehicles that are in individual customers order table
     */
    public function storeInvoiceVehicleForOrderUser(StoreInvoiceVehicleOrderUserRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {
            
            $user = auth()->user();
            $supplier = Supplier::find($user->id);

            $orderUser = OrderUser::find($request['order_user_id']);

            if ($orderUser->supplier_id !== $supplier->id) {
                // this vehicle is NOT be owned by the logged in driver
                return response()->json(['message' => 'invalid Order is selected for Vehicle PR Request. or the requested Order is not found. Deceptive request Aborted.'], 403);
            }

            $uuidTransactionIdSystem = Str::uuid(); // this uuid should be generated OUTSIDE the FOREACH to Generate COMMON and SAME uuid (i.e. transaction_id_system) for ALL invoices that have similar invoice_code (or for all invoices created in one PR request)

            // todays date
            $today = now()->format('Y-m-d');


            if ($orderUser->begin_date === null) {
                return response()->json(['message' => 'you can not ask PR for this Order. because this Order Begin Date is null, this order is not STARTED. order: ' . $orderUser->id . ' , the order Begin Date must be set before asking PR for it.'], 428);
            }
            //
            // Check if the begin_date is a valid date
            if (strtotime($orderUser->begin_date) === false) {
                return response()->json(['message' => 'The Order Begin Date is not a valid date.'], 400);
            }

            // lets check the order actual status
            if ($orderUser->status === OrderUser::ORDER_STATUS_PENDING) {
                return response()->json(['message' => 'you can not ask PR for an order with a status PENDING. order: ' . $orderUser->id . ' , an order must have status START or COMPLETE to be eligible for PR asking.'], 422);
            }
            if ($orderUser->status === OrderUser::ORDER_STATUS_SET) {
                return response()->json(['message' => 'you can not ask PR for an order with a status SET. the order is only accepted and not started. order: ' . $orderUser->id . ' , an order must have status START or COMPLETE to be eligible for PR asking.'], 422);
            }
            

            // lets check the orderUser vehicle_pr_status
            if ($orderUser->vehicle_pr_status === OrderUser::VEHICLE_PR_LAST) {
                return response()->json(['message' => 'every Available PR request have been already asked for this order: ' . $orderUser->id . ' , The order have PR_LAST status.'], 409);
            }
            if ($orderUser->vehicle_pr_status === OrderUser::VEHICLE_PR_COMPLETED) {
                return response()->json(['message' => 'all PR is paid for this order: ' . $orderUser->id . ' , The order have PR_COMPLETED status.'], 409);
            }
            if ($orderUser->vehicle_pr_status === OrderUser::VEHICLE_PR_TERMINATED) {
                return response()->json(['message' => 'this order PR is terminated for some reason. please check with the system admin why it is terminated. order: ' . $orderUser->id . ' , The order have PR_TERMINATED status.'], 410);
            }




            // invoice date // from the request
            $invoiceRequestEndDateValue = Carbon::parse($request['end_date'])->toDateString();
            // order end date // from orders table in the database
            $orderEndDate = Carbon::parse($orderUser->end_date)->toDateString();
           

            if ($invoiceRequestEndDateValue >= $today) {
                return response()->json(['message' => 'invoice asking End date must be Less than Today.'], 400);
            }

            // invoice end_date from the request can NOT be greater than the order end_date. // but invoice end_date from the request can be EQUALs to the order end_date
            if ($invoiceRequestEndDateValue > $orderEndDate) {
                return response()->json(['message' => 'invoice end_date in the request can NOT be greater than the order end_date.    invoice asking end_date can NOT be after the date the order ends'], 400);
            }


            if ($orderUser->vehicle_pr_status === null) {
                // this means the PR request we are making is for the first time (PR request made for FIRST time)
                // in this case we include the order begin_date to calculate the price
                // this means = (DATE DIFFERENCE + 1) // because the begin_date is entitled for payment also


                // order begin date // from orders table in the database
                $orderBeginDate = Carbon::parse($orderUser->begin_date)->toDateString();
                
                // invoice end_date from the request can NOT be less than the order begin_date. // but invoice end_date from the request can be EQUALs to the order begin_date
                if ($invoiceRequestEndDateValue < $orderBeginDate) {
                    return response()->json(['message' => 'invoice asking end_date in the request can NOT be less than the order begin_date.    invoice asking end_date can NOT be before the date the order begins'], 400);
                }


            }
            else if ($orderUser->vehicle_pr_status === OrderUser::VEHICLE_PR_STARTED && InvoiceVehicle::where('order_user_id', $orderUser->id)->exists()) {
                // this means the PR request we are making is NOT for the first time (PR request made for SUBSEQUENT time)
                // in this case we does NOT include the last invoice end_date to calculate the price BECAUSE that date is considered in price calculation in the last invoice
                // this means only = (DATE DIFFERENCE) // only the actual subtraction will be used


                $unPaidOrderInvoiceExist = InvoiceVehicle::where('order_user_id', $orderUser->id)
                                ->where('status', InvoiceVehicle::INVOICE_STATUS_NOT_PAID)
                                ->exists();
                
                if ($unPaidOrderInvoiceExist) {
                    return response()->json([
                        'message' => 'there is NOT_PAID invoice in invoice_vehicles table with this orderUser, you need to ask the system admins to pay the previous PR for this particular order before asking another PR for this particular Order: ' . $orderUser->id,
                        'order_user_id' => $orderUser->id,
                        'order_vehicle_plate_number' => $orderUser->vehicle->plate_number
                    ], 428);
                }


                // lets get the last invoice asked with this order_user_id
                $lastInvoice = $orderUser->invoiceVehicles()->latest()->first();

                if (!$lastInvoice) {
                    return response()->json(['message' => 'The last invoice asked for this order can not be found. This Invoice Can NOT be Processed'], 500);
                }


                // the last asked invoice end_date of this order // from invoices table in the database
                $lastInvoiceEndDate = Carbon::parse($lastInvoice->end_date)->toDateString();

                // invoice end_date from the request can NOT be Less than or Equals to the last invoice end_date. // invoice end_date from the request should always be greater than the last invoice end_date
                if ($invoiceRequestEndDateValue <= $lastInvoiceEndDate) {
                    return response()->json(['message' => 'invoice end_date in the request can NOT be Less than or Equal to the last asked invoice (end_date) of this order.    invoice end_date from the request should always be greater than the last asked invoice (end_date) of this order'], 422);
                }
                


            }
            else {
                return response()->json(['message' => 'This Invoice Can NOT be Processed'], 422);
            }



            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




            // invoice date // from the request
            $invoiceRequestEndDate = Carbon::parse($request['end_date']); // because we need this for calculation we removed the toDateString
            $invoiceRequestEndDateStringVersion = Carbon::parse($request['end_date'])->toDateString(); // this is used for comparison in the following if condition
            // order end date // from orders table in the database
            $orderEndDate = Carbon::parse($orderUser->end_date)->toDateString();

            // get the daily price of the order vehicle_name_id from contract_details_table;
            $orderPricePerDay = $orderUser->price_vehicle_payment;

            
            if ($invoiceRequestEndDateStringVersion < $orderEndDate) {
                $vehiclePrStatus = OrderUser::VEHICLE_PR_STARTED;
            }
            if ($invoiceRequestEndDateStringVersion === $orderEndDate) {
                $vehiclePrStatus = OrderUser::VEHICLE_PR_LAST;
            }
            

            
            if ($orderUser->vehicle_pr_status === null) {
                // this means the PR request we are making is for the first time (PR request made for FIRST time)
                // in this case we include the order begin_date to calculate the price
                // this means = (DATE DIFFERENCE + 1) // because the begin_date is entitled for payment also

                $invoiceStartDate = Carbon::parse($orderUser->begin_date); // because we need this for calculation we removed the toDateString

                // the diffInDays method in Carbon accurately calculates the difference in days between two dates, considering the specific dates provided, including the actual number of days in each month and leap years. 
                // It does not assume all months have a fixed number of days like 30 days.
                $differenceInDays = $invoiceRequestEndDate->diffInDays($invoiceStartDate);

                // this means = (DATE DIFFERENCE + 1) // because the order begin_date is entitled for payment also
                $differenceInDaysPlusBeginDate = $differenceInDays + 1;
                
                $invoicePriceAmountOfAllAskedDays = $differenceInDaysPlusBeginDate * $orderPricePerDay;



            }
            else if ($orderUser->vehicle_pr_status === OrderUser::VEHICLE_PR_STARTED && InvoiceVehicle::where('order_user_id', $orderUser->id)->exists()) {
                // this means the PR request we are making is NOT for the first time (PR request made for SUBSEQUENT time)
                // in this case we does NOT include the last invoice end_date to calculate the price BECAUSE that date is considered in price calculation in the last invoice
                // this means only = (DATE DIFFERENCE) // only the actual subtraction will be used


                // lets get the last invoice asked with this order_user_id
                $lastInvoice = $orderUser->invoiceVehicles()->latest()->first();

                if (!$lastInvoice) {
                    return response()->json(['message' => 'The last invoice asked for this order can not be found. This Invoice Can NOT be Processed'], 500);
                }


                // the last asked invoice end_date of this order // from invoices table in the database
                $invoiceStartDate = Carbon::parse($lastInvoice->end_date); // because we need this for calculation we removed the toDateString

                // the diffInDays method in Carbon accurately calculates the difference in days between two dates, considering the specific dates provided, including the actual number of days in each month and leap years. 
                // It does not assume all months have a fixed number of days like 30 days.
                $differenceInDays = $invoiceRequestEndDate->diffInDays($invoiceStartDate);

                $invoicePriceAmountOfAllAskedDays = $differenceInDays * $orderPricePerDay;


            }
            else {
                return response()->json(['message' => 'This Invoice Can NOT be Processed'], 422);
            }

            

            $invoiceVehicle = InvoiceVehicle::create([
                'order_user_id' => $request['order_user_id'],
                'supplier_id' => $orderUser->supplier_id,

                'transaction_id_system' => $uuidTransactionIdSystem,

                'start_date' => $invoiceStartDate,
                'end_date' => $request['end_date'],

                'price_amount' => $invoicePriceAmountOfAllAskedDays,
                'status' => InvoiceVehicle::INVOICE_STATUS_NOT_PAID,
                'paid_date' => null,                           // is NULL when the invoice is created initially, // and set when the invoice is paid by the organization                                                                                  
            ]);
            //
            if (!$invoiceVehicle) {
                return response()->json(['message' => 'InvoiceVehicle Create Failed'], 500);
            }


            $success = $orderUser->update([
                'vehicle_pr_status' => $vehiclePrStatus,
            ]);
            //
            if (!$success) {
                return response()->json(['message' => 'Order Update Failed'], 500);
            }



            return InvoiceVehicleForSupplierResource::make($invoiceVehicle->load('order', 'orderUser'));

        });

        return $var;
    }



    /**
     * Display the specified resource.
     */
    public function show(InvoiceVehicle $invoiceVehicle)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceVehicleOrderUserRequest $request, InvoiceVehicle $invoiceVehicle)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InvoiceVehicle $invoiceVehicle)
    {
        //
    }
}
