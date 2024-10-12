<?php

namespace App\Services\Api\V1\OrganizationUser;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Builder;

class PrPaymentService
{

    private $priceAmountTotalVal;
    private $invoiceCodeVal;

    public function __construct($priceAmountTotalValue, $invoiceCodeValue)
    {
        $this->priceAmountTotalVal = $priceAmountTotalValue;
        $this->invoiceCodeVal = $invoiceCodeValue;
    }

    public function payPrs() 
    {
        // to use ($this->var or $this->function()) te function must be NON-Static,  if it is static i should use (self::var or self::function)

        echo "Price Amount Total Value: " . $this->priceAmountTotalVal . "<br>";
        echo "Invoice Code Value: " . $this->invoiceCodeVal . "<br>";



        $verify = false;
        try {
            $header = PrPaymentService::populateRequestHeader(); 
            $body = $this->populateRequestData();

            $response = Http::withHeaders($header)
                ->withOptions(['verify' => $verify])
                ->post(config('boa.payment-endpoint'), $body);
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
        $payload = [
            'amount' => (string) $this->priceAmountTotalVal,
            'callBackURL' => config('BOA.notify_url'),
            'companyName' => config('BOA.company-name'),
            'key' => config('BOA.key'),
            'token' => $this->getBOAToken(),
            'invoiceCode' => (string) $this->invoiceCodeVal,
            'transactionTime' => now()->format('Y-m-d h:i:s A'),            
        ];

        $signature = $this->createSignature($payload);
        $payload['signature'] = $signature; // adds the signature key with the value to the array
        unset($payload['key']); // removes the key with the value from the array

        return $payload;
    }


    private function getBOAToken(): string
    {
        if ($token = auth()->user()->token) {
            return $token;
        } else {
            Log::alert('BOA: Could not find cbe token for the authenticated user!');
            abort(403, 'Could not find cbe token for the authenticated user!');
        }
    }


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

    public function sha256ToString($strJson)
    {
        // ths Signature does not use HMAC (hmac)
        $utf8String = mb_convert_encoding($strJson, 'UTF-8');
        $sha256Hash = hash("sha256", $utf8String);
        return $sha256Hash;
    }





}