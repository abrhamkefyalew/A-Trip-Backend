<?php

namespace App\Services\Api\V1\OrganizationUser\Payment\BOA;

use App\Models\Invoice;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

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
        /*
        $invoice = Invoice::where('invoice_code', self::$invoiceCodeVal)->get();

        // Check if all invoices have the same transaction_id_system            // all invoices should have the same transaction_id_system (i.e. uuid), since we are going to send that transaction_id_system (i.e. uuid) to BOA
                
        $transactionSystemUUIDs = $invoice->pluck('transaction_id_system')->unique();
        if ($transactionSystemUUIDs->count() > 1) {
            return response()->json(['message' => 'All invoices must have the same transaction_id_system.'], 422);
        }
        if ($transactionSystemUUIDs->count() < 1) {
            return response()->json(['message' => 'no valid transaction_id_system for the invoice.'], 422);
        }
        // Now we are sure all the invoices have the same transaction_id_system
        // So let's get that one transaction_id_system      // it is worth to mention that the following collection only have one transaction_id_system
        // Now $uuidTransactionIdSystem contains the transaction_id_system that can be used in the making of the payload for BOA
        $uuidTransactionIdSystem = $transactionSystemUUIDs->first(); // Retrieves the first transaction_id_system FROM our collection which in fact at this stage have ONLY one transaction_id_system  
        */

        /* FOR TEST */
        $uuidTransactionIdSystem = Str::uuid();


        $boaData = [
            'access_key' => 'b13653780c403ab28836f1fd7547d093',
            'amount' => (string) self::$priceAmountTotalVal,
            'currency' => 'ETB',

            'locale' => 'en',
            // 'payment_method' => 'card',
            'profile_id' => '6B8919B9-5598-4C07-950C-AAEE72F165AC',

            'reference_number' => (string) "OPR-1000", // universal_id
            'signed_date_time' => gmdate("Y-m-d\TH:i:s\Z"), // str(signed_date_time)
            'signed_field_names' => 'access_key,amount,currency,locale,profile_id,reference_number,signed_date_time,signed_field_names,transaction_type,transaction_uuid,unsigned_field_names', // the order of the field name matters significantly when signing and unsigning

            'transaction_type' => 'sale',
            'transaction_uuid' => (string) $uuidTransactionIdSystem, // universal_id (i.e. UUID)
            'unsigned_field_names' => '',
        ];


        $boaData = $boaData + ['signature' => self::sign($boaData)];


        // $renderedView = View::make('boa_pay', ['boaData' => $boaData])->render(); 
        // $renderedView = View::make('boa_pay_javascript', ['boaData' => $boaData])->render(); 
        $renderedView = View::make('boa_pay_js', ['boaData' => $boaData])->render(); 
         return $renderedView;

        // return $boaData;
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
}
