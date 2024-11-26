<?php

namespace App\Services\Api\V1\Callback\OrganizationUser\TeleBirr;

use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * handle different kinds of Payment CALLBACKs for organization with different methods within this same class
 * i.e. PR payment Callback for organization - or -  any other payment Callback for organization
 * 
 */
class TeleBirrOrganizationCallbackService
{

    /**
     * Handles PR payment callback
     * 
     * // good names : - // processBankCallback // handleBankCallback // executeAfterBankCallback // performOperationOnBankCallback // onBankCallbackDoOperation // processPaymentByTeleBirrForPRCallback // handlePaymentByTeleBirrForPRCallback
     * 
     */
    public function handlePaymentForPRCallback($invoiceReferenceWithPrefixFromTeleBirr)
    {
        // first lets strip out "OPR-" prefix from the invoice code we got
        $invoiceCode = substr($invoiceReferenceWithPrefixFromTeleBirr, 4); // Start from the 5th character onwards // THIS WORKS
        // $invoiceCode = str_replace("OPR-", "", $invoiceReferenceWithPrefixFromTeleBirr);                        // This Works ALSO

        Log::info('prefixed-invoice_code: '. $invoiceReferenceWithPrefixFromTeleBirr);
        Log::info('invoice_code: '. $invoiceCode);

        DB::transaction(function () use ($invoiceCode) {
            
            // if paid status code from the bank is NOT 200 -> i will log and abort // abrham samson check
            // if paid status code from the bank is 200,  ->  I wil do the following // abrham samson check


            // $invoiceIdList = [];


            // Fetch all invoices where invoice_code matches the one from the request
            $invoices = Invoice::where('invoice_code', $invoiceCode)->get(); // this should NOT be exists().  this should be get(), because i am going to use actual data (records) of $invoices in the below foreach
            //
            // i used ->isEmpty() - (i.e. if ($invoices->isEmpty())) // because $invoices is a collection using if (!$invoices) will create a problem
            if ($invoices->isEmpty()) { 
                Log::alert('TeleBirr callback: invoice (invoices) does not exist with the provided invoice_code!. invoice_code!: '. $invoiceCode);
                abort(422, 'TeleBirr callback: invoice (invoices) does not exist with the provided invoice_code!. invoice_code!: '. $invoiceCode);
            }


            $this->updateInvoice($invoiceCode); // Update all invoices with the sent invoice_code


            // in the following i am going to update the PARENT ORDER of each INVOICE one by one

            // 1. using foreach // THIS WORKS
            foreach ($invoices as $invoice) {
                $orderPrStatus = $this->getOrderPrStatus($invoice);

                $this->updatedOrder($invoice, $orderPrStatus);

                // $invoiceIdList[] = $invoice->id;
            }

            // 2. using each // THIS WORKS ALSO // NOT Tested though
            // $invoices->each(function ($invoice) {
            //     $orderPrStatus = $this->getOrderPrStatus($invoice->order);

            //     $this->updatedOrder($invoice->order, $orderPrStatus);

            //     // $invoiceIdList[] = $invoice->id;
            // });
            




            // since it is call back we will not return value to the banks
            // or may be 200 OK response // check abrham samson
            //
            // // Fetch the above updated invoices based on the invoice ids
            // $invoicesData = Invoice::whereIn('id', $invoiceIdList)->with('order')->latest()->get();

            // return InvoiceForOrganizationResource::collection($invoicesData);


        });

        // return $var;
    }





    







    private function getOrderPrStatus($invoice)
    {
        if ($invoice->order->pr_status === Order::ORDER_PR_STARTED) {
            return Order::ORDER_PR_STARTED;
        } else if ($invoice->order->pr_status === Order::ORDER_PR_LAST) {
            return Order::ORDER_PR_COMPLETED;

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
            return Order::ORDER_PR_COMPLETED;
                    // CURRENTLY THIS WILL NOT HAPPEN BECAUSE , I AM HANDLING IT WHEN 'SUPER_ADMIN' ASKS PR
                        //
                        // i added this condition because (IN CASE I DID NOT HANDLE THIS CASE when PR IS ASKED BY 'SUPER_ADMIN' - the following may happen) 
                                //
                                // a multiple pr request can be made to the same order in consecutive timelines one after the other 
                                // and from those invoices that are asked of the same order if the last invoice is asked of that order then the pr_status of the order would be PR_LAST
                                // and if we pay any one of that order invoice, the order pr_status will be changed from PR_LAST to PR_COMPLETED
                                // so when paying the rest of the invoices of that same order we must set the variable $orderPrStatus value (to PR_COMPLETED), even if the order shows PR_COMPLETED
                                // this way we will have a variable to assign to the pr_status of order table as we did below (i.e = 'pr_status' => $orderPrStatus,)

        } else {
            Log::alert('TeleBirr callback: invalid order PR status for organization!. PR_STATUS: ' . $invoice->order->pr_status);
            abort(422, 'TeleBirr callback: invalid order PR status for organization!. PR_STATUS: ' . $invoice->order->pr_status);
        }

    }



    private function updateInvoice($invoiceCodeVal)
    {
        $today = now()->format('Y-m-d');

        // Update all invoices with the sent invoice_code
        $success = Invoice::where('invoice_code', $invoiceCodeVal)->update([
            'status' => Invoice::INVOICE_STATUS_PAID,
            'paid_date' => $today,
        ]);
        // Handle invoice update failure
        if (!$success) {
            Log::alert('TeleBirr callback: Invoice Update Failed for organization!');
            abort(500, 'TeleBirr callback: Invoice Update Failed for organization!');
        }

    }


    private function updatedOrder($invoice, $orderPrStatus)
    {
        // Update the order pr_status
        $successTwo = $invoice->order()->update([
            'pr_status' => $orderPrStatus,
        ]);
        // Handle order update failure
        if (!$successTwo) {
            Log::alert('TeleBirr callback: Order Update Failed for organization!');
            abort(500, 'TeleBirr callback: Order Update Failed for organization!');
        }

    }

}