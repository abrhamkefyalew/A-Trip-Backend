<?php

namespace App\Services\Api\V1\Admin\Payment\TeleBirr;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TeleBirrVehiclePaymentService
{    

    public function initiatePaymentToVehicle($amount, $reason, $transactionId)
    {
        try {
            $telebirrRequestUrl = env('TELEBIRR_REQUEST_URL');
            $thirdPartyID = env('THIRD_PARTY_ID');
            $password = env('PASSWORD');
            $resultURL = env('RESULT_URL');
            $identifier = env('IDENTIFIER');
            $securityCredential = env('SECURITY_CREDENTIAL');
            $shortCode = env('SHORT_CODE');
            $receiverIdentifier = env('RECEIVER_IDENTIFIER');
            
            // $amount = str($this->payment_transx->payment_amount);
            // $reason = $this->payment_transx->reason;
            // $transactionId = $this->payment_transx->xref;
            $timestamp = Carbon::now()->format('YmdHis');


            $initiateRequestData = "
                <soapenv:Envelope xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/' xmlns:com='http://cps.huawei.com/cpsinterface/common' xmlns:api='http://cps.huawei.com/cpsinterface/api_requestmgr' xmlns:req='http://cps.huawei.com/cpsinterface/request'>
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
            ";

            // Send SOAP request
            $response = Http::withHeaders([
                'Content-Type' => 'text/xml',
            ])->post($telebirrRequestUrl, $initiateRequestData);

            // Parse SOAP response if necessary
            $responseContent = $response->body();

            return $responseContent;

        } catch (\Exception $e) {
            // Handle exceptions
            Log::alert('TeleBirr Vehicle Payment (Payment to Vehicle):  failed, ERROR : - ' . $e->getMessage());
        }
    }

}