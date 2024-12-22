<?php

namespace App\Services\Api\V1\Admin\Payment\TeleBirr;

use Carbon\Carbon;
use SimpleXMLElement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TeleBirrVehiclePaymentServiceMock
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
            // $timestamp = (new DateTime())->format("YmdHis");


            /*
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
            */
            



            

            $initiateRequestData = <<<XML
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
            


            // dd($initiateRequestData);

            return $initiateRequestData;




/*            
$initiateRequestData = <<<XML
<soapenv:Envelope
	xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
	xmlns:req="http://cps.huawei.com/cpsinterface/request">
	<soapenv:Header/>
	<soapenv:Body>
		<req:RequestMsg>
			<![CDATA[
			<?xml version="1.0" encoding="UTF-8"?><Request><KeyOwner>1</KeyOwner><Identity><Caller><CallerType>2</CallerType><ThirdPartyID>{$thirdPartyID}</ThirdPartyID><Password>{$password}</Password><ResultURL>{$resultURL}</ResultURL></Caller><Initiator><IdentifierType>11</IdentifierType><Identifier>{$identifier}</Identifier><SecurityCredential>{$securityCredential}</SecurityCredential><ShortCode>{$shortCode}</ShortCode></Initiator><ReceiverParty><IdentifierType>1</IdentifierType><Identifier>{$receiverIdentifier}</Identifier></ReceiverParty></Identity><Transaction><CommandID>InitTrans_2304</CommandID><Timestamp>{$timestamp}</Timestamp><Parameters><Parameter><Key>Amount</Key><Value>{$amount}</Value></Parameter><Parameter><Key>Currency</Key><Value>ETB</Value></Parameter><Parameter><Key>ReasonType</Key><Value>Pay for Individual B2C_VDF_Demo</Value></Parameter><Parameter><Key>Remark</Key><Value>{$reason}</Value></Parameter></Parameters><ReferenceData><ReferenceItem><Key>POSDeviceID</Key><Value>POS234789</Value></ReferenceItem></ReferenceData></Transaction></Request>]]>
		</req:RequestMsg>
	</soapenv:Body>
</soapenv:Envelope>
XML;
*/





/*
$initiateRequestData = "
<soapenv:Envelope
	xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/'
	xmlns:req='http://cps.huawei.com/cpsinterface/request'>
	<soapenv:Header/>
	<soapenv:Body>
		<req:RequestMsg>
			<![CDATA[
			<?xml version='1.0' encoding='UTF-8'?><Request><KeyOwner>1</KeyOwner><Identity><Caller><CallerType>2</CallerType><ThirdPartyID>{$thirdPartyID}</ThirdPartyID><Password>{$password}</Password><ResultURL>{$resultURL}</ResultURL></Caller><Initiator><IdentifierType>11</IdentifierType><Identifier>{$identifier}</Identifier><SecurityCredential>{$securityCredential}</SecurityCredential><ShortCode>{$shortCode}</ShortCode></Initiator><ReceiverParty><IdentifierType>1</IdentifierType><Identifier>{$receiverIdentifier}</Identifier></ReceiverParty></Identity><Transaction><CommandID>InitTrans_2304</CommandID><Timestamp>{$timestamp}</Timestamp><Parameters><Parameter><Key>Amount</Key><Value>{$amount}</Value></Parameter><Parameter><Key>Currency</Key><Value>ETB</Value></Parameter><Parameter><Key>ReasonType</Key><Value>Pay for Individual B2C_VDF_Demo</Value></Parameter><Parameter><Key>Remark</Key><Value>{$reason}</Value></Parameter></Parameters><ReferenceData><ReferenceItem><Key>POSDeviceID</Key><Value>POS234789</Value></ReferenceItem></ReferenceData></Transaction></Request>]]>
		</req:RequestMsg>
	</soapenv:Body>
</soapenv:Envelope>
";
*/


/*
$initiateRequestData = "
<soapenv:Envelope xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/' xmlns:com='http://cps.huawei.com/cpsinterface/common' xmlns:api='http://cps.huawei.com/cpsinterface/api_requestmgr' xmlns:req='http://cps.huawei.com/cpsinterface/request'>
    <soapenv:Header/>
    <soapenv:Body>
        <api:Request>
            <req:Header>
                <req:Version>1.0</req:Version>
                <req:CommandID>InitTrans_2003</req:CommandID>
                <req:OriginatorConversationID>123456789</req:OriginatorConversationID>
                <req:Caller>
                    <req:CallerType>2</req:CallerType>
                    <req:ThirdPartyID>AdiamatTrading</req:ThirdPartyID>
                    <req:Password>+P0dZnDwl61Hx+D5EhDKtwZOyV9vfymkhx5TMDjQyx4=</req:Password>
                    <req:ResultURL>eyewtw</req:ResultURL>
                </req:Caller>
                <req:KeyOwner>1</req:KeyOwner>
                <req:Timestamp>1733054928</req:Timestamp>
            </req:Header>
            <req:Body>
                <req:Identity>
                    <req:Initiator>
                        <req:IdentifierType>12</req:IdentifierType>
                        <req:Identifier>5133611</req:Identifier>
                        <req:SecurityCredential>PGZKqOv64CxUWIO9QW2320N+I9de3SJDid+BQhmT88g=</req:SecurityCredential>
                        <req:ShortCode>513361</req:ShortCode>
                    </req:Initiator>
                    <req:ReceiverParty>
                        <req:IdentifierType>1</req:IdentifierType>
                        <req:Identifier>0921169521</req:Identifier>
                    </req:ReceiverParty>
                </req:Identity>
                <req:TransactionRequest>
                    <req:Parameters>
                        <req:Amount>1</req:Amount>
                        <req:Currency>ETB</req:Currency>
                    </req:Parameters>
                </req:TransactionRequest>
                <req:ReferenceData>
                    <req:ReferenceItem>
                        <com:Key>Remarks</com:Key>
                        <com:Value>dhdfutyu</com:Value>
                    </req:ReferenceItem>
                </req:ReferenceData>
            </req:Body>
        </api:Request>
    </soapenv:Body>
</soapenv:Envelope>
";
*/

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








    /**
     * We are going to handle the following TWO different XML responses (XML values), using ONE CODE
     * 
     * using the following Single Code, (in single execution)
     *  // we are going to handle the following two Different XML Values (i.e. responses)
     *      1. Fail Xml Response
     *      3. Success XML Response
     */
    public function xmlReadingTest()
    {
     
        /*
        // FAIL XML Response ,   FAULT = (<soapenv:Fault)
        $xmlResponse = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <soapenv:Fault xmlns:axis2ns9="http://www.w3.org/2003/05/soap-envelope">
                <faultcode>axis2ns9:Sender</faultcode>
                <faultstring>Internal Server Exception</faultstring>
                <detail />
                </soapenv:Fault>
            </soapenv:Body>
            </soapenv:Envelope>
            XML;
        */


        
        // SUCCESS XML Response ,   SUCCESSFUL = (<api:Response)
        $xmlResponse = <<<XML
            <?xml version="1.0" encoding="utf-8"?>
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <api:Response xmlns:api="http://cps.huawei.com/cpsinterface/api_requestmgr"
                xmlns:res="http://cps.huawei.com/cpsinterface/response">
                <res:Header>
                    <res:Version>1.0</res:Version>
                    <res:OriginatorConversationID>1733085530</res:OriginatorConversationID>
                    <res:ConversationID>AG_20241201_70100345c6ca05176645</res:ConversationID>
                </res:Header>
                <res:Body>
                    <res:ResponseCode>0</res:ResponseCode>
                    <res:ResponseDesc>Accept the service request successfully.</res:ResponseDesc>
                    <res:ServiceStatus>0</res:ServiceStatus>
                </res:Body>
                </api:Response>
            </soapenv:Body>
            </soapenv:Envelope>
            XML;
        
            


            




            $xmlResponseObj = new SimpleXMLElement($xmlResponse);
            //
            // this URL (XPathNamespace) is for BOTH SUCCESS and FAIL XML response From Telebirr (<api:Response> and <soapenv:Fault>)
            $xmlResponseObj->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
            //
            // this URL (XPathNamespace) is exclusively only for the SUCCESS XML response From Telebirr (<api:Response>)
            $xmlResponseObj->registerXPathNamespace('api', 'http://cps.huawei.com/cpsinterface/api_requestmgr');
            $xmlResponseObj->registerXPathNamespace('res', 'http://cps.huawei.com/cpsinterface/response');
            //
            // this URL (XPathNamespace) is exclusively only for the FAULT XML response From Telebirr (<soapenv:Fault>)
            $xmlResponseObj->registerXPathNamespace('axis2ns9', 'http://www.w3.org/2003/05/soap-envelope');


            /////////////////  Check if the response contains a FAULT element or SUCCESS element  ////////////////////////////////////////////////////
            //

            // Check if it's a fail XML response
            $faultElements = $xmlResponseObj->xpath('//soapenv:Body/soapenv:Fault');
            $successElements = $xmlResponseObj->xpath('//soapenv:Body/api:Response');

            //
            if (!empty($faultElements)) {
                // handle Fail XML response
                $faultCode = (string)$xmlResponseObj->xpath('//soapenv:Body/soapenv:Fault/faultcode')[0] ?? null;
                $faultString = (string)$xmlResponseObj->xpath('//soapenv:Body/soapenv:Fault/faultstring')[0] ?? null;

                if ($faultCode !== null && $faultString !== null) {
                    // Handle fail XML response
                    return response()->json([
                        'message' => 'B2C TeleBirr Vehicle Payment (Payment to Vehicle) FAIL (Failed because Telebirr Responded Fault XML) - faultcode: ' . $faultCode . ', faultstring: ' . $faultString,
                        'faultCode' => $faultCode,
                        'faultstring' => $faultString,
                    ], 200);
                }
            }
            else if (!empty($successElements)) {

                // body
                $responseCode = (string)$xmlResponseObj->xpath('//soapenv:Body/api:Response/res:Body/res:ResponseCode')[0];
                $responseDesc = (string)$xmlResponseObj->xpath('//soapenv:Body/api:Response/res:Body/res:ResponseDesc')[0];
                $serviceStatus = (string)$xmlResponseObj->xpath('//soapenv:Body/api:Response/res:Body/res:ServiceStatus')[0];


                // header
                $transactionIdSystem = (string)$xmlResponseObj->xpath('//soapenv:Body/api:Response/res:Header/res:OriginatorConversationID')[0];
                $transactionIdBanks = (string)$xmlResponseObj->xpath('//soapenv:Body/api:Response/res:Header/res:ConversationID')[0];
            
                return response()->json([
                    'message' => 'B2C TeleBirr Vehicle Payment (Payment to Vehicle) SUCCESS.  ResponseCode: ' . $responseCode . ', ResponseDesc: ' . $responseDesc . ', ServiceStatus: ' . $serviceStatus . ', OriginatorConversationID: ' . $transactionIdSystem . ', ConversationID: ' . $transactionIdBanks,
                    'ResponseCode' => $responseCode,
                    'ResponseDesc' => $responseDesc,
                    'ServiceStatus' => $serviceStatus,
                    'OriginatorConversationID' => $transactionIdSystem,
                    'ConversationID' => $transactionIdBanks,
                ], 200);

            }

    }






}




