<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\Bid;
use App\Models\OrderUser;
use App\Models\InvoiceUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerRequests\PayInvoiceCallBackTelebirrRequest;

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
     * Display the specified resource.
     */
    public function show(InvoiceUser $invoiceUser)
    {
        //
    }



    /**
     * telebirr call back , to confirm payment // for organization
     */
    public function payInvoiceCallBackTelebirr(PayInvoiceCallBackTelebirrRequest $request)
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
                
                // LOG it here                                            return response()->json(['message' => 'the invoice_user_id does not exist'], 403); // change this to log
                Log::alert('BOA: the invoice_user_id does not exist!');
                abort(403, 'the invoice_user_id does not exist!');
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
                abort(422, 'Invoice Update Failed!');
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
                return response()->json(['message' => 'Order Update Failed'], 422);
            }


            // DELETE All the BIDS of this ORDER
            // // Soft delete all Bid records with order_user_id equal to $bid->order->id
            // Bid::where('order_user_id', $bid->order->id)->delete();
            //
            // // Force delete all Bid records with order_user_id equal to $bid->order->id
            Bid::where('order_user_id', $invoiceUser->orderUser->id)->forceDelete();

            

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
