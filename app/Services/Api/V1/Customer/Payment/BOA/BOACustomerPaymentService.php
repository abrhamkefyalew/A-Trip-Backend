<?php

namespace App\Services\Api\V1\Customer\Payment\BOA;

use Carbon\Carbon;
use App\Models\InvoiceUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Eloquent\Builder;

/**
 * handle different kinds of PAYMENTs for customer with different methods within this same class (including initial payment and final payment)
 * i.e. Vehicle Rent INITIAL Payment for customer, Vehicle Rent FINAL Payment for customer   - or -  any other Payment for customer
 * 
 */
class BOACustomerPaymentService
{
    private $invoiceUserIdVal;
    private $invoiceUserIdValWithPrefixInitial;
    private $invoiceUserIdValWithPrefixFinal;

    public function __construct($invoiceUserIdValue)
    {
        
        $this->invoiceUserIdVal = $invoiceUserIdValue;

    }

    

    
    /**
     * Handles Vehicle Rent Initial payment
     * 
     */
    public function initiateInitialPaymentForVehicle()
    {
        $invoiceUser = InvoiceUser::where('id', $this->invoiceUserIdVal)->get();

        

        // at last 
        // add prefix = "ICI-" : - prefix on the invoice id variable so that during call back later we could know that it is for INDIVIDUAL CUSTOMER INITIAL payment
        $this->invoiceUserIdValWithPrefixInitial = "ICI-" . (string) $this->invoiceUserIdVal; // add the ICI- prefix to indicate the invoice code is for INDIVIDUAL CUSTOMER INITIAL payment // we will use it later when the callback comes from the banks

        $boaData = [
            'access_key' => config('boa.testing') ? config('boa.testing_access_key') : config('boa.access_key'),
            'amount' => (string) $invoiceUser->price,
            'currency' => config('boa.testing') ? config('boa.testing_currency') : config('boa.currency'),

            'locale' => config('boa.testing') ? config('boa.testing_locale') : config('boa.locale'),
            // 'payment_method' => 'card',
            'profile_id' => config('boa.testing') ? config('boa.testing_profile_id') : config('boa.profile_id'),

            'reference_number' => (string) $this->invoiceUserIdValWithPrefixInitial,
            'signed_date_time' => gmdate("Y-m-d\TH:i:s\Z"), // str(signed_date_time)
            'signed_field_names' => 'access_key,amount,currency,locale,profile_id,reference_number,signed_date_time,signed_field_names,transaction_type,transaction_uuid,unsigned_field_names', // the order of the field name matters significantly when signing and unsigning

            'transaction_type' => config('boa.testing') ? config('boa.testing_transaction_type') : config('boa.transaction_type'),
            'transaction_uuid' => (string) $invoiceUser->transaction_id_system, // universal_id (i.e. UUID)
            'unsigned_field_names' => '',
        ];


        $boaData = $boaData + ['signature' => $this->sign($boaData)];
        // return $boaData; // IF YOU WANT TO return the payload it self
        

        
        // DIRECTLY CALL THE VIEW (i.e.boa_pay.blade.php) and return the RENDERED VIEW
        //
        // $renderedView = View::make('boa_pay_organization_using_payload_directly_javascript', ['boaData' => $boaData])->render(); // passing payload directly
        $renderedView = View::make('boa_pay_organization_using_payload_directly', ['boaData' => $boaData])->render(); // passing payload directly
        return $renderedView;
        
    }




    /**
     * Handles Vehicle Rent Final payment
     * 
     */
    public function initiateFinalPaymentForVehicle()
    {
        $invoiceUser = InvoiceUser::where('id', $this->invoiceUserIdVal)->get();

        

        // at last 
        // add prefix = "ICF-" : - prefix on the invoice id variable so that during call back later we could know that it is for INDIVIDUAL CUSTOMER Final payment
        $this->invoiceUserIdValWithPrefixFinal = "ICF-" . (string) $this->invoiceUserIdVal; // add the ICF- prefix to indicate the invoice code is for INDIVIDUAL CUSTOMER Final payment // we will use it later when the callback comes from the banks

        
        $boaData = [
            'access_key' => config('boa.testing') ? config('boa.testing_access_key') : config('boa.access_key'),
            'amount' => (string) $invoiceUser->price,
            'currency' => config('boa.testing') ? config('boa.testing_currency') : config('boa.currency'),

            'locale' => config('boa.testing') ? config('boa.testing_locale') : config('boa.locale'),
            // 'payment_method' => 'card',
            'profile_id' => config('boa.testing') ? config('boa.testing_profile_id') : config('boa.profile_id'),

            'reference_number' => (string) $this->invoiceUserIdValWithPrefixFinal,
            'signed_date_time' => gmdate("Y-m-d\TH:i:s\Z"), // str(signed_date_time)
            'signed_field_names' => 'access_key,amount,currency,locale,profile_id,reference_number,signed_date_time,signed_field_names,transaction_type,transaction_uuid,unsigned_field_names', // the order of the field name matters significantly when signing and unsigning

            'transaction_type' => config('boa.testing') ? config('boa.testing_transaction_type') : config('boa.transaction_type'),
            'transaction_uuid' => (string) $invoiceUser->transaction_id_system, // universal_id (i.e. UUID)
            'unsigned_field_names' => '',
        ];


        $boaData = $boaData + ['signature' => $this->sign($boaData)];
        // return $boaData; // IF YOU WANT TO return the payload it self
        

        
        // DIRECTLY CALL THE VIEW (i.e.boa_pay.blade.php) and return the RENDERED VIEW
        //
        // $renderedView = View::make('boa_pay_organization_using_payload_directly_javascript', ['boaData' => $boaData])->render(); // passing payload directly
        $renderedView = View::make('boa_pay_organization_using_payload_directly', ['boaData' => $boaData])->render(); // passing payload directly
        return $renderedView;
    }






    public function sign($params)
    {
        $secretKey = config('boa.testing') ? config('boa.testing_secret_key') : config('boa.secret_key');

        return $this->signData($this->buildDataToSign($params), $secretKey);
    }

    public function signData($data, $secretKey)
    {
        return base64_encode(hash_hmac('sha256', $data, $secretKey, true));
    }

    public function buildDataToSign($params)
    {
        $signedFieldNames = explode(',', $params['signed_field_names']);

        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field.'='.$params[$field];
        }

        $x = $this->commaSeparate($dataToSign);
        Log::info('Data to sign: '.$x);

        return $x;
    }

    public function commaSeparate($dataToSign)
    {
        return implode(',', $dataToSign);
    }


}