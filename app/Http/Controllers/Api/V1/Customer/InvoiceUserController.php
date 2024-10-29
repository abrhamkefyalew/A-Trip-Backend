<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\Bid;
use App\Models\Customer;
use App\Models\OrderUser;
use App\Models\InvoiceUser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerRequests\PayInvoiceFinalRequest;
use App\Services\Api\V1\Customer\Payment\BOA\BOACustomerPaymentService;
use App\Http\Requests\Api\V1\CustomerRequests\PayInvoiceCallbackTelebirrRequest;

class InvoiceUserController extends Controller
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
     * Pay the FINAL invoice payment for an order.
     */
    public function payInvoiceFinal(PayInvoiceFinalRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {
            // get the customer identity
            $user = auth()->user();
            $customer = Customer::find($user->id);


            if ($customer->is_active != 1) {
                return response()->json(['message' => 'Forbidden: Deactivated Customer'], 401); 
            }
            if ($customer->is_approved != 1) {
                return response()->json(['message' => 'Forbidden: NOT Approved Customer'], 401); 
            }


            $orderUser = OrderUser::find($request['order_user_id']);


            if ($customer->id != $orderUser->customer_id) {
                return response()->json(['message' => 'invalid Order is selected or Requested. or the requested Order is not found. Deceptive request Aborted.'], 403);
            }


            if ($orderUser->paid_complete_status == 1) {
                return response()->json(['message' => 'this Order is already PAID in full. Payment has been already completed for this order'], 409); 
            }

            if ($orderUser->status == OrderUser::ORDER_STATUS_PENDING) {
                return response()->json(['message' => 'this Order is NOT eligible for final payment. Because the order have PENDING status. order should be accepted , started or completed to be eligible for final payment'], 428); 
            }


            $invoiceUserCheck = InvoiceUser::where('order_user_id', $orderUser->id)->where('status', InvoiceUser::INVOICE_STATUS_PAID)->whereNotNull('paid_date')->exists();
            if ($invoiceUserCheck == false) {
                return response()->json(['message' => 'this Order is NOT eligible for final payment. because its initial payment is not payed yet'], 428); 
            }


            $previouslyPaidAmount = InvoiceUser::where('order_user_id', $orderUser->id)
                ->where('status', InvoiceUser::INVOICE_STATUS_PAID)
                ->whereNotNull('paid_date')
                ->sum('price');

            $requiredPaymentAmount = $orderUser->price_total - $previouslyPaidAmount;
            $paymentAmountFromRequest = (int) $request['price_amount_total'];

            if ($paymentAmountFromRequest < $requiredPaymentAmount) {
                return response()->json(['error' => 'Insufficient amount. Please pay the required amount.'], 422);
            }


            if (InvoiceUser::where('order_user_id', $orderUser->id)->where('status', InvoiceUser::INVOICE_STATUS_NOT_PAID)->where('paid_date', null)->exists()) {
                // remove all the previous unpaid invoices for that order
                $successForceDelete = InvoiceUser::where('order_user_id', $orderUser->id)->where('status', InvoiceUser::INVOICE_STATUS_NOT_PAID)->where('paid_date', null)->forceDelete();
                //
                if (!$successForceDelete) {
                    return response()->json(['message' => 'Failed to DELETE Useless invoices'], 500);
                }
            }
            
            

            // generate Unique UUID for each individual Customer invoices
            $uuidTransactionIdSystem = Str::uuid(); // this uuid should be generated to be NEW and UNIQUE uuid (i.e. transaction_id_system) for Each invoice

            // create invoice for this order
            $invoiceUser = InvoiceUser::create([
                'order_user_id' => $orderUser->id,
                'transaction_id_system' => $uuidTransactionIdSystem,

                'price' => $requiredPaymentAmount,
                'status' => InvoiceUser::INVOICE_STATUS_NOT_PAID,
                'paid_date' => null,                           // is NULL when the invoice is created initially, // and set when the invoice is paid by the organization
                'payment_method' => $request['payment_method'],
            ]);
            //
            if (!$invoiceUser) {
                return response()->json(['message' => 'Invoice Create Failed'], 500);
            }


            
            /* START Payment Service Call */
            
            // do the actual payment 

            if ($request['payment_method'] = InvoiceUser::INVOICE_BOA) {

                // Setting values
                $boaCustomerPaymentService = new BOACustomerPaymentService($invoiceUser->id);

                // Calling a non static method
                $valuePaymentRenderedView = $boaCustomerPaymentService->initiateFinalPaymentForVehicle();

                return $valuePaymentRenderedView;
            }
            else {
                return response()->json(['error' => 'Invalid payment method selected.'], 422);
            }


            /* END Payment Service Call */



        });

        return $var;
    }




    /**
     * Display the specified resource.
     */
    public function show(InvoiceUser $invoiceUser)
    {
        //
    }



    /**
     * NOT FUNCTIONAL CURRENTLY. 
     * 
     * this function is made UN-functional currently
     * 
     * the functionality under it is moved to another class
     * 
     * 
     * 
     * telebirr call back , to confirm payment // for organization
     */
    public function payInvoiceCallbackTelebirr(PayInvoiceCallbackTelebirrRequest $request)
    {
        //
        DB::transaction(function () use ($request) {
            
            // if paid status code from the bank is NOT 200 -> i will log and abort
            // if paid status code from the bank is 200,  ->  I wil do the following
            


            // todays date
            $today = now()->format('Y-m-d');


            $invoiceUser = InvoiceUser::find($request['invoice_user_id']);

            if (!$invoiceUser) {
                // I CHECK condition because:- 
                            // because : - i Commented ('exists:invoice_users,id') in the request 
                                   // ('exists:invoice_users,id') is COMMENTED in the request Because: -  we have prefix on the invoice_user_id, (lke    "o1"-for organization  or   "i1"-for individual customer )
                                                                                                          // the prefix will not let us check the existence of the id in the database, 
                                                                                                          // so we have to do existence check manually in the controller // using this if condition
                
                // LOG it here                                            return response()->json(['message' => 'the invoice_user_id does not exist'], 404); // change this to log
                Log::alert('BOA: the invoice_user_id does not exist!');
                abort(404, 'the invoice_user_id does not exist!');
            }

            // Update the invoice status and paid date
            $success = $invoiceUser->update([
                'status' => InvoiceUser::INVOICE_STATUS_PAID,
                'paid_date' => $today,
            ]);
            //
            // Handle invoice update failure
            if (!$success) {
                Log::alert('BOA: Invoice Update Failed!');
                abort(500, 'Invoice Update Failed!');
            }


            $totalPaidAmountCheck = InvoiceUser::where('order_user_id', $invoiceUser->order_user_id)
                ->where('status', InvoiceUser::INVOICE_STATUS_PAID)
                ->whereNotNull('paid_date')
                ->sum('price');


            $orderPaidCompleteStatus = 0; // default value // initializing

            if ($totalPaidAmountCheck >= $invoiceUser->orderUser->price_total)
            {
                $orderPaidCompleteStatus = 1;
            }
            else if ($totalPaidAmountCheck < $invoiceUser->orderUser->price_total)
            {
                $orderPaidCompleteStatus = 0;
            }



            // Update the order paid_complete_status
            $successTwo = $invoiceUser->orderUser()->update([
                'paid_complete_status' => $orderPaidCompleteStatus,
                'status' => OrderUser::ORDER_STATUS_SET,
            ]);
            //
            // Handle order update failure
            if (!$successTwo) {
                return response()->json(['message' => 'Order Update Failed'], 500);
            }


           
            if (Bid::where('order_user_id', $invoiceUser->orderUser->id)->exists()) {
                // DELETE All the BIDS of this ORDER
                // // Soft delete all Bid records with order_user_id equal to $bid->order->id
                // Bid::where('order_user_id', $bid->order->id)->delete();
                //
                // // Force delete all Bid records with order_user_id equal to $bid->order->id
                $successForceDelete = Bid::where('order_user_id', $invoiceUser->orderUser->id)->forceDelete();
                //
                if (!$successForceDelete) {
                    Log::alert('TeleBirr callback: Bid Delete Failed! invoice_user_id: ' . $invoiceUser->id, 500);
                }

            }

            

            // since it is call back we will not return value to the banks
            // or may be 200 OK response // check abrham samson


        });

        // return $var;


    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InvoiceUser $invoiceUser)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InvoiceUser $invoiceUser)
    {
        //
    }
}
