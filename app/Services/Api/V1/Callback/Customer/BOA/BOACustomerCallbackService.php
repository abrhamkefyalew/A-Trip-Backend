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
class BOACustomerCallbackService
{
    private $invoiceUserId;

    public function __construct($invoiceReferenceWithPrefixFromBoa)
    {
        // first lets strip out "ICI-" prefix from the invoice we got
        $this->invoiceUserId = substr($invoiceReferenceWithPrefixFromBoa, 4); // Start from the 5th character onwards // THIS WORKS
        // $this->$invoiceUserIdInitialPayment = str_replace("ICI-", "", $invoiceReferenceWithPrefixFromBoa);         // This Works ALSO
        // $this->$invoiceUserIdFinalPayment = str_replace("ICF-", "", $invoiceReferenceWithPrefixFromBoa);           // This Works ALSO
    }


    public function handleInitialPaymentForVehicleCallback()
    {
        // if paid status code from the bank is NOT 200 -> i will log and abort
        // if paid status code from the bank is 200,  ->  I wil do the following

        $invoiceUser = $this->findInvoiceUser();

        DB::transaction(function () use ($invoiceUser) {
            $this->updateInvoiceUser($invoiceUser);
            $this->updateOrderUser($invoiceUser, true); // TRUE param is to indicate: - update 'status' column of Orders table
            $this->deleteAssociatedBids($invoiceUser);
        });
    }

    public function handleFinalPaymentForVehicleCallback()
    {
        // if paid status code from the bank is NOT 200 -> i will log and abort
        // if paid status code from the bank is 200,  ->  I wil do the following
        
        $invoiceUser = $this->findInvoiceUser();
        
        DB::transaction(function () use ($invoiceUser) {
            $this->updateInvoiceUser($invoiceUser);
            $this->updateOrderUser($invoiceUser, false); // FALSE param is to indicate: - do NOT update 'status' column of Orders table
        });
    }











    private function findInvoiceUser()
    {
        $invoiceUser = InvoiceUser::find($this->invoiceUserId);
        //
        if (!$invoiceUser) {
            $this->logAndAbort('the invoice with invoice_user_id does not exist! invoice_user_id: ' . $this->invoiceUserId, 404);
        }

        return $invoiceUser;
    }

    private function updateInvoiceUser($invoiceUser)
    {
        $today = now()->format('Y-m-d');

        $success = $invoiceUser->update([
            'status' => InvoiceUser::INVOICE_STATUS_PAID,
            'paid_date' => $today,
        ]);
        //
        if (!$success) {
            $this->logAndAbort('Invoice Update Failed! invoice_user_id: ' . $invoiceUser->id, 500);
        }
    }

    private function updateOrderUser($invoiceUser, $updateStatusColumn)
    {
        $totalPaidAmountCheck = InvoiceUser::where('order_user_id', $invoiceUser->order_user_id)
            ->where('status', InvoiceUser::INVOICE_STATUS_PAID)
            ->whereNotNull('paid_date')
            ->sum('price');
            
        //
        if (!$invoiceUser->orderUser) {
            $this->logAndAbort('the parent order of the Invoice is not Found! invoice_user_id: ' . $invoiceUser->id, 404);
        }

        $orderPaidCompleteStatus = 0; // default value // initializing

        if ($totalPaidAmountCheck >= $invoiceUser->orderUser->price_total)
        {
            $orderPaidCompleteStatus = 1;
        }
        else if ($totalPaidAmountCheck < $invoiceUser->orderUser->price_total)
        {
            $orderPaidCompleteStatus = 0;
        }

        // UPDATE ORDER
        
        // METHOD 1 // this WORKS
        $updateFields = [
            'paid_complete_status' => $orderPaidCompleteStatus,
        ];

        // for initial payment callback we ALSO Update 'STATUS' column
        if ($updateStatusColumn == true) {
            $updateFields['status'] = OrderUser::ORDER_STATUS_SET; // append new key => value ('status' key with OrderUser::ORDER_STATUS_SET value) to the array updateFields
        }

        $success = $invoiceUser->orderUser()->update($updateFields);
        //
        if (!$success) {
            $this->logAndAbort('Order Update Failed! invoice_user_id: ' . $invoiceUser->id, 500);
        }


        // // METHOD 2 // this WORKS also // NOT tested though
        // $invoiceUser->orderUser->paid_complete_status = $orderPaidCompleteStatus;

        // // for initial payment callback we ALSO Update 'STATUS' column
        // if ($updateStatusColumn) {
        //     $invoiceUser->orderUser->status = OrderUser::ORDER_STATUS_SET;
        // }

        // $success = $invoiceUser->orderUser->save();
        // //
        // if (!$success) {
        //     $this->logAndAbort('Order Update Failed!');
        // }
    }

    private function deleteAssociatedBids($invoiceUser)
    {
        $success = Bid::where('order_user_id', $invoiceUser->orderUser->id)->forceDelete();
        //
        if (!$success) {
            $this->logAndAbort('Bid Delete Failed! invoice_user_id: ' . $invoiceUser->id, 500);
        }
    }

    private function logAndAbort($message, $statusCode)
    {
        Log::alert('BOA callback: ' . $message);
        abort($statusCode, 'BOA callback: ' . $message);
    }



}