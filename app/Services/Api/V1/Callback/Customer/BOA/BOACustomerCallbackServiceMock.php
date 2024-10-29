<?php

namespace App\Services\Api\V1\Callback\Customer\BOA;

use App\Models\Bid;
use App\Models\OrderUser;
use App\Models\InvoiceUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * handle different kinds of PAYMENT CALLBACKs for customer with different methods within this same class (including initial payment CALLBACKs and final payment CALLBACKs)
 * i.e. Vehicle Rent INITIAL Payment CALLBACK for customer, Vehicle Rent FINAL Payment CALLBACK for customer   - or -  any other Payment for customer
 * 
 */
class BOACustomerCallbackServiceMock
{
    private $invoiceUserIdVal;

    public function __construct($invoiceReferenceWithPrefixFromBoa)
    {
        // first lets strip out "ICI-" prefix from the invoice we got
        $this->invoiceUserIdVal = substr($invoiceReferenceWithPrefixFromBoa, 4); // Start from the 5th character onwards // THIS WORKS
        // $this->$invoiceUserIdInitialPaymentVal = str_replace("ICI-", "", $invoiceReferenceWithPrefixFromBoa);         // This Works ALSO
        // $this->$invoiceUserIdFinalPaymentVal = str_replace("ICF-", "", $invoiceReferenceWithPrefixFromBoa);           // This Works ALSO
    }





    /**
     * Handles initial vehicle payment Callback
     * 
     */
    public function handleInitialPaymentForVehicleCallback()
    {

        // Get the invoice id from the global variable
        $invoiceUserId = $this->invoiceUserIdVal;

        DB::transaction(function () use ($invoiceUserId) {
            
            // if paid status code from the bank is NOT 200 -> i will log and abort
            // if paid status code from the bank is 200,  ->  I wil do the following
            


            // todays date
            $today = now()->format('Y-m-d');


            $invoiceUser = InvoiceUser::find($invoiceUserId);

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
                    Log::alert('BOA callback: Bid Delete Failed! invoice_user_id: ' . $invoiceUser->id, 500);
                }

            }
            

            // since it is call back we will not return value to the banks
            // or may be 200 OK response // check abrham samson


        });

        // return $var;
        
    }




    /**
     * Handles Final vehicle payment Callback
     * 
     */
    public function handleFinalPaymentForVehicleCallback()
    {
        // Get the invoice id from the global variable
        $invoiceUserId = $this->invoiceUserIdVal;

        DB::transaction(function () use ($invoiceUserId) {
            
            // if paid status code from the bank is NOT 200 -> i will log and abort
            // if paid status code from the bank is 200,  ->  I wil do the following
            


            // todays date
            $today = now()->format('Y-m-d');


            $invoiceUser = InvoiceUser::find($invoiceUserId);

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
            ]);
            //
            // Handle order update failure
            if (!$successTwo) {
                return response()->json(['message' => 'Order Update Failed'], 500);
            }


            

            // since it is call back we will not return value to the banks
            // or may be 200 OK response // check abrham samson


        });

        // return $var;

    }



}