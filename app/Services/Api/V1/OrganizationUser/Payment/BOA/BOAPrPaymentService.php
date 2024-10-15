<?php

namespace App\Services\Api\V1\OrganizationUser\Payment\BOA;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Builder;

class BOAPrPaymentService
{

    private $priceAmountTotalVal;
    private $invoiceCodeVal;

    public function __construct($priceAmountTotalValue, $invoiceCodeValue)
    {
        $this->priceAmountTotalVal = $priceAmountTotalValue;
        $this->invoiceCodeVal = $invoiceCodeValue;
    }

    public function initiatePayment() 
    {
        

        // echo "Price Amount Total Value: " . $this->priceAmountTotalVal . "<br>";
        // echo "Invoice Code Value: " . $this->invoiceCodeVal . "<br>";



        $verify = false;
        try {
            $header = $this->populateRequestHeader(); 
            $body = $this->populateRequestData();

            $response = Http::withHeaders($header)
                // ->withOptions(['verify' => $verify])
                ->post('https://testsecureacceptance.cybersource.com/pay', $body);
        } catch (\Exception $e) {
            Log::alert('BOA: Error when initiating payment. Message: '.$e->getMessage());

            abort(400, 'Error when initiating payment.'.$e->getMessage());
        }



        // do the actual payment operation here
        // if success it will return true or success or some kind of string
        return "payment link";
    }


    private function populateRequestHeader(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->getBOAToken(),
        ];
    }


    private function populateRequestData(): array
    {   
        $uuidTransactionIdSystem = Str::uuid();
        
        // $signedDateTime = Carbon::now()->format('Y-m-d\TH:i:s\Z');

        $signedDateTime = gmdate("Y-m-d\TH:i:s\Z");
        
        $allFieldsToSign = [
            'access_key' => 'b13653780c403ab28836f1fd7547d093',
            'amount' => (string) $this->priceAmountTotalVal,
            'currency' => 'ETB',

            'locale' => 'en',
            // 'payment_method' => 'card',
            'profile_id' => '6B8919B9-5598-4C07-950C-AAEE72F165AC',

            'reference_number' => (string) "OPR-1000", // universal_id
            'signed_date_time' => $signedDateTime, // str(signed_date_time)
            'signed_field_names' => 'access_key,amount,currency,locale,payment_method,profile_id,reference_number,signed_date_time,signed_field_names,transaction_type,transaction_uuid,unsigned_field_names',

            'transaction_type' => 'sale',
            'transaction_uuid' => (string) $uuidTransactionIdSystem,## universal_id
            'unsigned_field_names' => '',

            // 'callBackURL' => config('BOA.notify_url'),
            // 'companyName' => config('BOA.company-name'),
            // 'key' => config('BOA.key'),
            // 'token' => $this->getBOAToken(),
            // 'invoiceCode' => (string) $this->invoiceCodeVal,
            // 'transactionTime' => now()->format('Y-m-d h:i:s A'),            
        ];


        /* START NOT USED OLD CODE */
        // $signature = $this->createSignature($allFieldsToSign);
        // $payload['signature'] = $signature; // adds the signature key with the value to the array
        // unset($payload['key']); // removes the key with the value from the array
        /* END NOT USED OLD CODE */

        $signatureBoa = $this->sign($allFieldsToSign);
        $allFieldsToSign['signature'] = $signatureBoa;

              
        return $allFieldsToSign;
    }


    private function getBOAToken(): string
    {
        if ($token = auth()->user()->token) {
            return $token;
        } else {
            Log::alert('BOA: Could not find boe token for the authenticated user!');
            abort(403, 'Could not find boe token for the authenticated user!');
        }
    }


    // NOT USED
    public function createSignature($payload)
    {
        // sorts the Array using the key in alphabetical order
        ksort($payload);
        Log::info('BOA: Sorted the Array - - - - - - : ', $payload);

        $temp = [];
        foreach ($payload as $key => $value) {
            $temp[] = sprintf('%s=%s', $key, $value);
        }
        $s = implode('&', $temp);

        Log::info('BOA: string to hash: '.$s);

        return $this->sha256ToString($s);
    }

    // NOT USED
    public function sha256ToString($strJson)
    {
        // ths Signature does not use HMAC (hmac)
        $utf8String = mb_convert_encoding($strJson, 'UTF-8');
        $sha256Hash = hash("sha256", $utf8String);
        return $sha256Hash;
    }




    public function sign($fields) {
        $secretKey = "8f707468b3ee47678f8d96ee425c1e63a32898506ac14217bd198c47cbe89809c810244decd04306aae257cd43647cffcb97f66c2b414455b4745b7a96ef13014e86de215fbb4d4f9531c16d082482ba4972524e0810496aa61511e919c2d45221851e63832340089bf3486d5025456de9cb7c01dd0841f6a27817a061f26b77"; // Replace with your actual secret key
    
        // Sort fields alphabetically
        ksort($fields);
        
        $encodedFields = implode(",", array_map(
            function ($key, $value) {
                return $key . "=" . $value;
            },
            array_keys($fields),
            $fields
        ));
    
        // Generate the HMAC-SHA256 hash
        $signature = hash_hmac('sha256', $encodedFields, $secretKey);
    
        return $signature;
    }
    
 
 



}