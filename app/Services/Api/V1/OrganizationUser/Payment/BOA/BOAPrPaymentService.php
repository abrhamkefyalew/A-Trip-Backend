<?php

namespace App\Services\Api\V1\OrganizationUser\Payment\BOA;

use App\Models\Invoice;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Http;

class BOAPrPaymentService
{

    private static $priceAmountTotalVal;
    private static $invoiceCodeVal;

    public static function setValues($priceAmountTotalValue, $invoiceCodeValue)
    {
        self::$priceAmountTotalVal = $priceAmountTotalValue;
        self::$invoiceCodeVal = $invoiceCodeValue;
    }

    public static function initiateBoaPayment()
    {
        
        
        
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
        




        $boaData = [
            'access_key' => 'b13653780c403ab28836f1fd7547d093',
            'amount' => (string) self::$priceAmountTotalVal,
            'currency' => 'ETB',

            'locale' => 'en',
            // 'payment_method' => 'card',
            'profile_id' => '6B8919B9-5598-4C07-950C-AAEE72F165AC',

            'reference_number' => (string) self::$invoiceCodeVal, // invoice id
            'signed_date_time' => gmdate("Y-m-d\TH:i:s\Z"), // str(signed_date_time)
            'signed_field_names' => 'access_key,amount,currency,locale,profile_id,reference_number,signed_date_time,signed_field_names,transaction_type,transaction_uuid,unsigned_field_names', // the order of the field name matters significantly when signing and unsigning

            'transaction_type' => 'sale',
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




    




    public static function sign($params)
    {
        $secretKey = config('boa.testing') ? config('boa.testing_secret_key') : config('boa.secret_key');

        return self::signData(self::buildDataToSign($params), $secretKey);
    }

    public static function signData($data, $secretKey)
    {
        return base64_encode(hash_hmac('sha256', $data, $secretKey, true));
    }

    public static function buildDataToSign($params)
    {
        $signedFieldNames = explode(',', $params['signed_field_names']);

        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field.'='.$params[$field];
        }

        $x = self::commaSeparate($dataToSign);
        Log::info('Data to sign: '.$x);

        return $x;
    }

    public static function commaSeparate($dataToSign)
    {
        return implode(',', $dataToSign);
    }






















    public static function initiateBoaPaymentTest()
    {
        
        
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
            'amount' => (string) self::$priceAmountTotalVal,
            'currency' => 'ETB',

            'locale' => 'en',
            // 'payment_method' => 'card',
            'profile_id' => '6B8919B9-5598-4C07-950C-AAEE72F165AC',

            'reference_number' => (string) self::$invoiceCodeVal, // invoice id
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
        return $invoice; // if you want to return the invoice MODEL 
        */

        
        // DIRECTLY CALL THE VIEW (i.e.boa_pay.blade.php) and return the RENDERED VIEW
        //
        // $renderedView = View::make('boa_pay_organization_using_payload_directly_javascript', ['boaData' => $boaData])->render(); // passing payload directly
        $renderedView = View::make('boa_pay_organization_using_payload_directly', ['boaData' => $boaData])->render(); // passing payload directly
        return $renderedView;
        

    }



}
