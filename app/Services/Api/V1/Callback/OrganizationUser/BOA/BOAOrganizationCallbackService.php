<?php

namespace App\Services\Api\V1\Callback\OrganizationUser\BOA;

use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * handle different kinds of Payment CALLBACKs for organization with different methods within this same class
 * i.e. PR payment Callback for organization - or -  any other payment Callback for organization
 * 
 */
class BOAOrganizationCallbackService
{
    private static $invoiceCodeVal;

    public static function setValues($invoiceReferenceWithPrefixFromBoa)
    {
        // first lets strip out "OPR-" prefix from the invoice code we got
        self::$invoiceCodeVal = substr($invoiceReferenceWithPrefixFromBoa, 4); // Start from the 5th character onwards // THIS WORKS
        // self::$invoiceCodeVal = str_replace("OPR-", "", $invoiceReferenceWithPrefixFromBoa);                        // This Works ALSO
    }

    // good names // processBankCallback // handleBankCallback // executeAfterBankCallback // performOperationOnBankCallback // onBankCallbackDoOperation // processPaymentByBoaForPRCallback // handlePaymentByBoaForPRCallback
    //

    /**
     * Handles PR payment
     *  initiatePaymentByBoaForPR
     */
    public static function handlePaymentByBoaForPRCallback()
    {

        // Get the invoice_code from the global variable
        $invoiceCode = self::$invoiceCodeVal;

        DB::transaction(function () use ($invoiceCode) {

            // if paid status code from the bank is NOT 200 -> i will log and abort // abrham samson check
            // if paid status code from the bank is 200,  ->  I wil do the following // abrham samson check



            // todays date
            $today = now()->format('Y-m-d');



            /* $invoiceIdList = []; */


            // Fetch all invoices where invoice_code matches the one from the request
            $invoices = Invoice::where('invoice_code', $invoiceCode)->get(); // this should NOT be exists().  this should be get(), because i am going to use actual data (records) of $invoices in the below foreach
            //
            if (!$invoices) {
                // I must CHECK this condition 
                Log::alert('BOA: the invoice_code does not exist!');
                abort(403, 'the invoice_code does not exist!');
            }



            // Update all invoices with the sent invoice_code
            $success = Invoice::where('invoice_code', $invoiceCode)->update([
                'status' => Invoice::INVOICE_STATUS_PAID,
                'paid_date' => $today,
            ]);
            // Handle invoice update failure
            if (!$success) {
                return response()->json(['message' => 'Invoice Update Failed'], 422);
            }


            // in the following foreach i am going to update the PARENT ORDER of each INVOICE one by one
            foreach ($invoices as $invoice) {
                if ($invoice->order->pr_status === Order::ORDER_PR_STARTED) {
                    $orderPrStatus = Order::ORDER_PR_STARTED;
                } else if ($invoice->order->pr_status === Order::ORDER_PR_LAST) {
                    
                    $orderPrStatus = Order::ORDER_PR_COMPLETED;

                    // this is no longer used since i am controlling it in invoice asking,
                    // which means in invoice asking i will prevent super_admin not ask another invoice for an order if there is an already UnPaid invoice for that order in invoices table
                    // $orderInvoicesPaymentCheck = Invoice::where('order_id', $invoice->order->id)
                    //                 ->where('status', Invoice::INVOICE_STATUS_NOT_PAID)
                    //                 ->get();

                    // if ($orderInvoicesPaymentCheck->isEmpty()) {
                    //     $orderPrStatus = Order::ORDER_PR_COMPLETED;
                    // } else {
                    //     $orderPrStatus = Order::ORDER_PR_LAST;
                    // }
                    
                } else if ($invoice->order->pr_status === Order::ORDER_PR_COMPLETED) { 
                    // CURRENTLY THIS WILL NOT HAPPEN BECAUSE , I AM HANDLING IT WHEN 'SUPER_ADMIN' ASKS PR
                        //
                        // i added this condition because (IN CASE I DID NOT HANDLE THIS CASE when PR IS ASKED BY 'SUPER_ADMIN' - the following may happen) 
                                //
                                // a multiple pr request can be made to the same order in consecutive timelines one after the other 
                                // and from those invoices that are asked of the same order if the last invoice is asked of that order then the pr_status of the order would be PR_LAST
                                // and if we pay any one of that order invoice, the order pr_status will be changed from PR_LAST to PR_COMPLETED
                                // so when paying the rest of the invoices of that same order we must set the variable $orderPrStatus value (to PR_COMPLETED), even if the order shows PR_COMPLETED
                                // this way we will have a variable to assign to the pr_status of order table as we did below (i.e = 'pr_status' => $orderPrStatus,)
                    $orderPrStatus = Order::ORDER_PR_COMPLETED;
                }

                

                // Update the order pr_status
                $successTwo = $invoice->order()->update([
                    'pr_status' => $orderPrStatus,
                ]);
                // Handle order update failure
                if (!$successTwo) {
                    return response()->json(['message' => 'Order Update Failed'], 422);
                }

                /* $invoiceIdList[] = $invoice->id; */
            }

            // since it is call back we will not return value to the banks
            // or may be 200 OK response // check abrham samson
            //
            // // Fetch the above updated invoices based on the invoice ids
            // $invoicesData = Invoice::whereIn('id', $invoiceIdList)->with('order')->latest()->get();

            // return InvoiceForOrganizationResource::collection($invoicesData);
            
        });
        
    }


}