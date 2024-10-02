<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\InvoiceUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            
            // todays date
            $today = now()->format('Y-m-d');


            $invoiceUser = InvoiceUser::find($request['invoice_id']);

            // Update the invoice status and paid date
            $success = $invoiceUser->update([
                'status' => InvoiceUser::INVOICE_STATUS_PAID,
                'paid_date' => $today,
            ]);
            //
            // Handle invoice update failure
            if (!$success) {
                return response()->json(['message' => 'Invoice Update Failed'], 422);
            }


            $totalPayedAmountCheck = InvoiceUser::where('order_id', $invoiceUser->order_id)
                ->where('status', InvoiceUser::INVOICE_STATUS_PAID)
                ->whereNotNull('paid_date')
                ->sum('price');


            

            if ($totalPayedAmountCheck >= $invoiceUser->orderUser->price_total)
            {
                $orderPayedCompleteStatus = 1;
            }
            else if ($totalPayedAmountCheck < $invoiceUser->orderUser->price_total)
            {
                $orderPayedCompleteStatus = 0;
            }



            // Update the order payed_complete_status
            $successTwo = $invoiceUser->orderUser()->update([
                'payed_complete_status' => $orderPayedCompleteStatus,
            ]);
            //
            // Handle order update failure
            if (!$successTwo) {
                return response()->json(['message' => 'Order Update Failed'], 422);
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
