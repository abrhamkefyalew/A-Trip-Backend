<?php

namespace App\Services\Api\V1\OrganizationUser\Payment\BOA;

use App\Models\Invoice;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Http;

/**
 * handle different kinds of PAYMENTs for organization with different methods within this same class
 * i.e. PR Payment for organization  - or -  any other Payment for organization
 * 
 */
class BOAOrganizationPaymentService
{    
    /**
     * Handles PR payment
     * 
     */
    public function initiatePaymentForPR($priceAmountTotalVal, $invoiceCodeVal)
    {
        // $invoices = Invoice::where('invoice_code', $invoiceCodeVal)->get(); // multiple invoices will be fetched
        // // Check if all invoices have the same transaction_id_system            // all invoices should have the same transaction_id_system (i.e. uuid), since we are going to send that transaction_id_system (i.e. uuid) to BOA
                
        // $transactionSystemUUIDs = $invoices->pluck('transaction_id_system')->unique();
        // if ($transactionSystemUUIDs->count() > 1) {
        //     return response()->json(['message' => 'All invoices must have the same transaction_id_system.'], 422);
        // }
        // if ($transactionSystemUUIDs->count() < 1) {
        //     return response()->json(['message' => 'no valid transaction_id_system for the invoices.'], 422);
        // }
        // Now we are sure all the invoices have the same transaction_id_system
        // So let's get that one transaction_id_system      // it is worth to mention that the following collection only have one transaction_id_system
        // Now $uuidTransactionIdSystem contains the transaction_id_system that can be used in the making of the payload for BOA
        $uuidTransactionIdSystem = "2345"; // Retrieves the first transaction_id_system FROM our collection which in fact at this stage have ONLY one transaction_id_system  
        

        

        // at last 
        // add prefix = "OPR-" : - prefix on the invoice code variable so that during call back later we could know that it is for ORGANIZATION PR payment
        $invoiceCodeValWithPrefixPr = config('constants.payment.customer_to_business.organization_pr') . $invoiceCodeVal; // add the OPR- prefix to indicate the invoice code is for organization payment // we will use it later when the callback comes from the banks


        $boaData = [
            'access_key' => config('boa.testing') ? config('boa.testing_access_key') : config('boa.access_key'),
            'amount' => (string) $priceAmountTotalVal,
            'currency' => config('boa.testing') ? config('boa.testing_currency') : config('boa.currency'),

            'locale' => config('boa.testing') ? config('boa.testing_locale') : config('boa.locale'),
            // 'payment_method' => 'card',
            'profile_id' => config('boa.testing') ? config('boa.testing_profile_id') : config('boa.profile_id'),

            'reference_number' => (string) $invoiceCodeValWithPrefixPr,
            'signed_date_time' => gmdate("Y-m-d\TH:i:s\Z"), // str(signed_date_time)
            'signed_field_names' => 'access_key,amount,currency,locale,profile_id,reference_number,signed_date_time,signed_field_names,transaction_type,transaction_uuid,unsigned_field_names', // the order of the field name matters significantly when signing and unsigning

            'transaction_type' => config('boa.testing') ? config('boa.testing_transaction_type') : config('boa.transaction_type'),
            'transaction_uuid' => (string) $uuidTransactionIdSystem, // universal_id (i.e. UUID)
            'unsigned_field_names' => '',
        ];


        $boaData = $boaData + ['signature' => self::sign($boaData)];
        // return $boaData; // IF YOU WANT TO return the payload it self
        

        
        // DIRECTLY CALL THE VIEW (i.e.boa_pay.blade.php) and return the RENDERED VIEW
        //
        // $renderedView = View::make('boa_pay_organization_using_payload_directly_javascript', ['boaData' => $boaData])->render(); // passing payload directly
        $renderedView = View::make('boa_pay_organization_using_payload_directly', ['boaData' => $boaData])->render(); // passing payload directly
        return $renderedView;
        

    }




    




    protected static function sign($params)
    {
        $secretKey = config('boa.testing') ? config('boa.testing_secret_key') : config('boa.secret_key');

        return self::signData(self::buildDataToSign($params), $secretKey);
    }

    protected static function signData($data, $secretKey)
    {
        return base64_encode(hash_hmac('sha256', $data, $secretKey, true));
    }

    protected static function buildDataToSign($params)
    {
        $signedFieldNames = explode(',', $params['signed_field_names']);

        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field.'='.$params[$field];
        }

        $x = self::commaSeparate($dataToSign);
        Log::info('Data to sign: '.$x);

        return $x;
    }

    protected static function commaSeparate($dataToSign)
    {
        return implode(',', $dataToSign);
    }






















    public static function initiateBoaPaymentTest($priceAmountTotalVal, $invoiceCodeVal) // hardcoded json request // we use it for testing
    {
        // at last 
        // add prefix = "OPR-" : - prefix on the invoice code variable so that during call back later we could know that it is for ORGANIZATION PR payment
        $invoiceCodeValWithPrefixPr = "OPR-" . $invoiceCodeVal; // add the OPR- prefix to indicate the invoice code is for organization payment // we will use it later when the callback comes from the banks

        
        
        /*
        $invoices = Invoice::where('invoice_code', self::$invoiceCodeVal)->get(); // multiple invoices will be fetched
        // Check if all invoices have the same transaction_id_system            // all invoices should have the same transaction_id_system (i.e. uuid), since we are going to send that transaction_id_system (i.e. uuid) to BOA
                
        $transactionSystemUUIDs = $invoices->pluck('transaction_id_system')->unique();
        if ($transactionSystemUUIDs->count() > 1) {
            return response()->json(['message' => 'All invoices must have the same transaction_id_system.'], 422);
        }
        if ($transactionSystemUUIDs->count() < 1) {
            return response()->json(['message' => 'no valid transaction_id_system for the invoices.'], 422);
        }
        // Now we are sure all the invoices have the same transaction_id_system
        // So let's get that one transaction_id_system      // it is worth to mention that the following collection only have one transaction_id_system
        // Now $uuidTransactionIdSystem contains the transaction_id_system that can be used in the making of the payload for BOA
        $uuidTransactionIdSystem = $transactionSystemUUIDs->first(); // Retrieves the first transaction_id_system FROM our collection which in fact at this stage have ONLY one transaction_id_system  
        */


        $uuidTransactionIdSystem = Str::uuid(); //for production , take this value from the $invoices table


        
        $boaData = [
            'access_key' => 'b13653780c403ab28836f1fd7547d093',
            'amount' => (string) $priceAmountTotalVal,
            'currency' => 'ETB',

            'locale' => 'en',
            // 'payment_method' => 'card',
            'profile_id' => '6B8919B9-5598-4C07-950C-AAEE72F165AC',

            'reference_number' => (string) $invoiceCodeValWithPrefixPr,
            'signed_date_time' => gmdate("Y-m-d\TH:i:s\Z"), // str(signed_date_time)
            'signed_field_names' => 'access_key,amount,currency,locale,profile_id,reference_number,signed_date_time,signed_field_names,transaction_type,transaction_uuid,unsigned_field_names', // the order of the field name matters significantly when signing and unsigning

            'transaction_type' => 'sale',
            'transaction_uuid' => (string) $uuidTransactionIdSystem, // universal_id (i.e. UUID)
            'unsigned_field_names' => '',
        ];


        $boaData = $boaData + ['signature' => self::sign($boaData)];
        // return $boaData; // IF YOU WANT TO return the payload it self
        


        /*
        // return the invoice MODEL 
        //
        Invoice::where('invoice_code', self::$invoiceCodeVal)->update([
            'request_payload' => $boaData,
        ]);
        //  
        // $invoices->refresh(); // refresh() is not working // fetch it again as below
        //
        $invoice = Invoice::where('invoice_code', self::$invoiceCodeVal)->first();
        return $invoice; // if you want to return the invoice MODEL (i.e used return the $invoice instance to the CONTROLLER so the Controller could call the web.php route so that the web.php could call the blade VIEW (boa_pay.blade.php))
        */

        
        // DIRECTLY CALL THE VIEW (i.e.boa_pay.blade.php) and return the RENDERED VIEW
        //
        // $renderedView = View::make('boa_pay_organization_using_payload_directly_javascript', ['boaData' => $boaData])->render(); // passing payload directly
        $renderedView = View::make('boa_pay_organization_using_payload_directly', ['boaData' => $boaData])->render(); // passing payload directly
        return $renderedView;
        

    }



}
