<?php

namespace App\Services\Api\V1\Admin\Payment\TeleBirr;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Artisaninweb\SoapWrapper\SoapWrapper;
use SoapClient;

class TeleBirrVehiclePaymentService
{    

    // B2C
    public function initiatePaymentToVehicle($transactionId, $amount, $reason)
    {
        try {
            $telebirrRequestUrl = config('telebirr-b-to-c.testing') ? config('telebirr-b-to-c.request_url_testing') : config('telebirr-b-to-c.request_url');
            $resultURL = config('telebirr-b-to-c.testing') ? config('telebirr-b-to-c.result_url_testing') : config('telebirr-b-to-c.result_url');

            $thirdPartyID = config('telebirr-b-to-c.testing') ? config('telebirr-b-to-c.third_party_id_testing') : config('telebirr-b-to-c.third_party_id');
            $password = config('telebirr-b-to-c.testing') ? config('telebirr-b-to-c.password_testing') : config('telebirr-b-to-c.password');
            $identifier = config('telebirr-b-to-c.testing') ? config('telebirr-b-to-c.identifier_testing') : config('telebirr-b-to-c.identifier');
            $securityCredential = config('telebirr-b-to-c.testing') ? config('telebirr-b-to-c.security_credential_testing') : config('telebirr-b-to-c.security_credential');
            $shortCode = config('telebirr-b-to-c.testing') ? config('telebirr-b-to-c.short_code_testing') : config('telebirr-b-to-c.short_code');
            $receiverIdentifier = "251913780190";
            
            // $amount = str($this->payment_transx->payment_amount);
            // $reason = $this->payment_transx->reason;
            // $transactionId = $this->payment_transx->xref;
            $timestamp = Carbon::now()->format('YmdHis');
            // $timestamp = (string)time();
            // $timestamp = (new DateTime())->format("YmdHis");



/*
$soapRequestData = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:com="http://cps.huawei.com/cpsinterface/common" xmlns:api="http://cps.huawei.com/cpsinterface/api_requestmgr" xmlns:req="http://cps.huawei.com/cpsinterface/request">
    <soapenv:Header/>
    <soapenv:Body>
        <api:Request>
            <req:Header>
                <req:Version>1.0</req:Version>
                <req:CommandID>InitTrans_2003</req:CommandID>
                <req:OriginatorConversationID>{$transactionId}</req:OriginatorConversationID>
                <req:Caller>
                    <req:CallerType>2</req:CallerType>
                    <req:ThirdPartyID>{$thirdPartyID}</req:ThirdPartyID>
                    <req:Password>{$password}</req:Password>
                    <req:ResultURL>{$resultURL}</req:ResultURL>
                </req:Caller>
                <req:KeyOwner>1</req:KeyOwner>
                <req:Timestamp>{$timestamp}</req:Timestamp>
            </req:Header>
            <req:Body>
                <req:Identity>
                    <req:Initiator>
                        <req:IdentifierType>12</req:IdentifierType>
                        <req:Identifier>{$identifier}</req:Identifier>
                        <req:SecurityCredential>{$securityCredential}</req:SecurityCredential>
                        <req:ShortCode>{$shortCode}</req:ShortCode>
                    </req:Initiator>
                    <req:ReceiverParty>
                        <req:IdentifierType>1</req:IdentifierType>
                        <req:Identifier>{$receiverIdentifier}</req:Identifier>
                    </req:ReceiverParty>
                </req:Identity>
                <req:TransactionRequest>
                    <req:Parameters>
                        <req:Amount>{$amount}</req:Amount>
                        <req:Currency>ETB</req:Currency>
                    </req:Parameters>
                </req:TransactionRequest>
                <req:ReferenceData>
                    <req:ReferenceItem>
                        <com:Key>Remarks</com:Key>
                        <com:Value>{$reason}</com:Value>
                    </req:ReferenceItem>
                </req:ReferenceData>
            </req:Body>
        </api:Request>
    </soapenv:Body>
</soapenv:Envelope>
XML;
*/



$soapRequestData = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:api="http://cps.huawei.com/synccpsinterface/api_requestmgr" xmlns:req="http://cps.huawei.com/synccpsinterface/request" xmlns:com="http://cps.huawei.com/synccpsinterface/common">
    <soapenv:Header/>
    <soapenv:Body>
        <api:Request>
            <req:Header>
                <req:Version>1.0</req:Version>
                <req:CommandID>InitTrans_2003</req:CommandID>
                <req:OriginatorConversationID>{$transactionId}</req:OriginatorConversationID>
                <req:Caller>
                    <req:CallerType>2</req:CallerType>
                    <req:ThirdPartyID>{$thirdPartyID}</req:ThirdPartyID>
                    <req:Password>{$password}</req:Password>
                </req:Caller>
                <req:KeyOwner>1</req:KeyOwner>
                <req:Timestamp>{$timestamp}</req:Timestamp>
            </req:Header>
            <req:Body>
                <req:Identity>
                    <req:Initiator>
                        <req:IdentifierType>12</req:IdentifierType>
                        <req:Identifier>{$identifier}</req:Identifier>
                        <req:SecurityCredential>{$securityCredential}</req:SecurityCredential>
                        <req:ShortCode>{$shortCode}</req:ShortCode>
                    </req:Initiator>
                    <req:ReceiverParty>
                        <req:IdentifierType>1</req:IdentifierType>
                        <req:Identifier>{$receiverIdentifier}</req:Identifier>
                    </req:ReceiverParty>
                </req:Identity>
                <req:TransactionRequest>
                    <req:Parameters>
                        <req:Amount>{$amount}</req:Amount>
                        <req:Currency>ETB</req:Currency>
                    </req:Parameters>
                </req:TransactionRequest>
                <req:ReferenceData>
                    <req:ReferenceItem>
                        <com:Key>Remarks</com:Key>
                        <com:Value>{$reason}</com:Value>
                    </req:ReferenceItem>
                </req:ReferenceData>
            </req:Body>
        </api:Request>
    </soapenv:Body>
</soapenv:Envelope>
XML;


// return $soapRequestData;

  
            $client = new SoapClient(null, [
                'location' => $telebirrRequestUrl,
                'uri' => "http://schemas.xmlsoap.org/soap/envelope/",
                'trace' => 1
            ]);

            $response = $client->__doRequest($soapRequestData, $telebirrRequestUrl, '', SOAP_1_1);


            return $response;


        } catch (\Exception $e) {
            // Handle exceptions
            Log::alert('TeleBirr Vehicle Payment (Payment to Vehicle):  failed, ERROR : - ' . $e->getMessage());
        }
    }

}