<?php

namespace App\Services\Api\V1\Admin\Payment\TeleBirr;

use SoapClient;
use Carbon\Carbon;
use App\Models\Trip;
use App\Models\Order;
use SimpleXMLElement;
use App\Models\OrderUser;
use App\Models\InvoiceTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Artisaninweb\SoapWrapper\SoapWrapper;

class TeleBirrTripPaymentService
{    

    // these values hold the RESPONSE Values From TeleBirr
    private $transactionIdSystemVal;
    private $transactionIdBanksVal;

    // B2C
    public function initiatePaymentToTrip($transactionId, $amount, $reason, $receiverIdentifier)
    {
        try {
            $telebirrRequestUrl = config('telebirr-b-to-c.testing') ? config('telebirr-b-to-c.request_url_testing') : config('telebirr-b-to-c.request_url');
            $resultURL = config('telebirr-b-to-c.testing') ? config('telebirr-b-to-c.result_url_testing') : config('telebirr-b-to-c.result_url');

            $thirdPartyID = config('telebirr-b-to-c.testing') ? config('telebirr-b-to-c.third_party_id_testing') : config('telebirr-b-to-c.third_party_id');
            $password = config('telebirr-b-to-c.testing') ? config('telebirr-b-to-c.password_testing') : config('telebirr-b-to-c.password');
            $identifier = config('telebirr-b-to-c.testing') ? config('telebirr-b-to-c.identifier_testing') : config('telebirr-b-to-c.identifier');
            $securityCredential = config('telebirr-b-to-c.testing') ? config('telebirr-b-to-c.security_credential_testing') : config('telebirr-b-to-c.security_credential');
            $shortCode = config('telebirr-b-to-c.testing') ? config('telebirr-b-to-c.short_code_testing') : config('telebirr-b-to-c.short_code');
            
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


Log::info('B2C TeleBirr Trip Payment (Payment to Trip): REQUEST we SENT : - ' . $soapRequestData);

  
            $client = new SoapClient(null, [
                'location' => $telebirrRequestUrl,
                'uri' => "http://schemas.xmlsoap.org/soap/envelope/",
                'trace' => 1
            ]);

            $responseXml = $client->__doRequest($soapRequestData, $telebirrRequestUrl, '', SOAP_1_1);

            Log::info('B2C TeleBirr Trip Payment (Payment to Trip): RESPONSE XML: - ' . $responseXml);

            $xmlResponse = simplexml_load_string($responseXml);


            // ----------------------------------------------------- READ THE XML Response From Telebirr ---------------------------------------------------------------------------------------------//
            //
            //
            //
            $xmlResponseObj = new SimpleXMLElement($responseXml);
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



            /////////////////////////  Check if the response contains a FAULT element or SUCCESS element  ////////////////////////////////////////////////////
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
                        'message' => 'B2C TeleBirr Trip Payment (Payment to Trip) FAIL (Failed because Telebirr Responded Fault XML) - faultcode: ' . $faultCode . ', faultstring: ' . $faultString,
                        'faultCode' => $faultCode,
                        'faultstring' => $faultString,
                    ], 422);
                }
            } 
            else if (!empty($successElements)) {
                // THIS HAPPENs if the SENT XML is CORRECT
                // Payment Should be SUCCESS , but Still payment may NOT be Successful
                // if ResponseCode === 0          // if ResponseCode !== 0

                // This is a the response
                // Check for elements in the xml
                // 
                // Body of XML
                $responseCode = (string)$xmlResponseObj->xpath('//soapenv:Body/api:Response/res:Body/res:ResponseCode')[0];
                $responseDesc = (string)$xmlResponseObj->xpath('//soapenv:Body/api:Response/res:Body/res:ResponseDesc')[0];
                $serviceStatus = (string)$xmlResponseObj->xpath('//soapenv:Body/api:Response/res:Body/res:ServiceStatus')[0];
                //
                // Header of XML
                $transactionIdSystem = (string)$xmlResponseObj->xpath('//soapenv:Body/api:Response/res:Header/res:OriginatorConversationID')[0];
                $transactionIdBanks = (string)$xmlResponseObj->xpath('//soapenv:Body/api:Response/res:Header/res:ConversationID')[0];

                // assign them to global variables for they are to be used below
                $this->transactionIdSystemVal = $transactionIdSystem;
                $this->transactionIdBanksVal = $transactionIdBanks;

                $telebirrResponseParameters = [
                    'ResponseCode' => $responseCode,
                    'ResponseDesc' => $responseDesc,
                    'ServiceStatus' => $serviceStatus,
                    'OriginatorConversationID_or_transaction_id_system' => $transactionIdSystem,
                    'ConversationID_or_transaction_id_banks' => $transactionIdBanks,
                ];
                //
                Log::info("B2C TeleBirr Trip Payment (Payment to Trip): RESPONSE Converted to JSON" . json_encode($telebirrResponseParameters));


                // Check if the response code indicates success (You can define your own success code logic)
                if ($responseCode === '0') {
                    // This is the SUCCESSFUL response
                    Log::info('B2C TeleBirr Trip Payment (Payment to Trip) -------- SUCCESS ----------.  ResponseCode: ' . $responseCode . ', ResponseDesc: ' . $responseDesc . ', OriginatorConversationID (transaction_id_system): ' . $transactionIdSystem . ', ConversationID (transaction_id_banks): ' . $transactionIdBanks);
                

                    $responseValue = $this->handlePaymentToTripAfterTeleBirrResponse();


                    $valuePayment = [
                        'message' => 'B2C TeleBirr Trip Payment (Payment to Trip) SUCCESS.  ResponseCode: ' . $responseCode . ', ResponseDesc: ' . $responseDesc . ', OriginatorConversationID (transaction_id_system): ' . $transactionIdSystem . ', ConversationID (transaction_id_banks): ' . $transactionIdBanks,
                        'telebirr_response_parameters' => $telebirrResponseParameters,
                    ];


                    return $valuePayment;

                } 
                else if ($responseCode === '1001') {
                    Log::alert('B2C TeleBirr Trip Payment (Payment to Trip) FAIL due to Caller Authentication ERROR - ResponseCode: ' . $responseCode . ', ResponseDesc: ' . $responseDesc . ', OriginatorConversationID (transaction_id_system): ' . $transactionIdSystem . ', ConversationID (transaction_id_banks): ' . $transactionIdBanks);
                    
                    return response()->json(['message' => 'B2C TeleBirr Trip Payment (Payment to Trip) FAIL due to Caller Authentication ERROR - ResponseCode: ' . $responseCode . ', ResponseDesc: ' . $responseDesc . ', OriginatorConversationID (transaction_id_system): ' . $transactionIdSystem . ', ConversationID (transaction_id_banks): ' . $transactionIdBanks], 422);
                }
                else {
                    Log::alert('B2C TeleBirr Trip Payment (Payment to Trip) FAIL due to unknown response code. (ResponseCode is Neither 0 Nor 1001)');
                
                    return response()->json(['message' => 'B2C TeleBirr Trip Payment (Payment to Trip) FAIL due to unknown response code. (ResponseCode is Neither 0 Nor 1001)'], 422);
                }
            }
            else {
                Log::error('B2C TeleBirr Trip Payment (Payment to Trip) FAIL due to unknown ERRORs From TeleBirr Side. No valid xml or status code is found in TeleBirrs response');
            
                return response()->json(['message' => 'B2C TeleBirr Trip Payment (Payment to Trip) FAIL due to unknown ERRORs From TeleBirr Side. No valid xml or status code is found in TeleBirrs response'], 422);
            }


        } catch (\Exception $e) {
            // Handle exceptions
            Log::alert('B2C TeleBirr Trip Payment (Payment to Trip): FAIL, ERROR caught in CATCH=(catch of the try,catch): - ' . $e->getMessage());

            return response()->json(['message' => 'B2C TeleBirr Trip Payment (Payment to Trip): FAIL, ERROR caught in CATCH=(catch of the try,catch): - ' . $e->getMessage()], 500);
        }
    }

    // the logic implemented here about the (TRY-CATCH - & - ABORT)
    /*
        1. ONLY Abort
            If the `abort` statement is encountered and the code is not wrapped in a `try-catch` block:
            - The `abort` statement will immediately stop the execution of the script and return a response with the specified error message and status code.
            - The script will not continue execution beyond the `abort` statement.
                    //
                    SO ABORT is also enough, without the try-catch
                        - `abort` is designed to immediately stop the execution of the script and return a response with the specified error message and status code. 
                        - In scenarios where you intend to halt the script execution upon encountering a particular condition or error, using `abort` can be sufficient to handle such cases without the need for a surrounding `try-catch` block. 
                

        2. Abort with TRY-CATCH    
            If the `abort` statement is encountered and the code is wrapped in a `try-catch` block:
            - The `abort` statement will still immediately stop the execution of the script and return a response with the specified error message and status code.
            - The `catch` block will catch the exception thrown by the `abort` function, allowing you to handle the error, log it, and return an appropriate response.
            - The `try-catch` construct, with the `catch` block, will prevent the script from crashing completely due to the `abort`, but the execution will still stop at the point where the `abort` is encountered.
                    //
                    USE of the TRY-CATCH
                        - However, if you want to catch and handle the exception thrown by `abort`, or if you need to perform additional error handling or logging, then using a `try-catch` block around the potentially aborting code would be appropriate. 
                        - In summary, `abort` can effectively stop the script execution and return an error response without the necessity of a `try-catch` block, but the choice depends on your specific requirements for error handling and control flow in your application.
    */








    ////////////////////////////// the following handles THE SYSTEM LOGIC after i get a RESPONSE from TELEBIRR   //////////////////////////////////////////////////
    
    public function handlePaymentToTripAfterTeleBirrResponse()
    {

        $transactionIdSystemValue =  $this->transactionIdSystemVal;     

        $var = DB::transaction(function () use ($transactionIdSystemValue) {
            

            $invoiceTrip = InvoiceTrip::where('transaction_id_system', $transactionIdSystemValue)->first(); // this should NOT be exists().  this should be get(), because i am going to use actual data (records) of $invoices in the below foreach
            //
            if (!$invoiceTrip) { 
                Log::alert('B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Trip Payment (Payment to Trip): the InvoiceTrip with the transaction_id_system is not found. transaction_id_system: ' . $this->transactionIdSystemVal);                
                abort(500, 'B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Trip Payment (Payment to Trip): the InvoiceTrip with the transaction_id_system is not found. transaction_id_system: ' . $this->transactionIdSystemVal);
            }


            $success = $invoiceTrip->update([
                'status' => InvoiceTrip::INVOICE_STATUS_PAID,
            ]);
            //
            if (!$success) {
                abort(500, 'InvoiceTrip Update Failed');
            }


            


            $trip = Trip::find($invoiceTrip->trip_id);
            //
            if (!$trip) { 
                Log::alert('B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Trip Payment (Payment to Trip): the Trip with trip_id is not found. transaction_id_system: ' . $invoiceTrip->trip_id);                
                abort(500, 'B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Trip Payment (Payment to Trip): the Trip with trip_id is not found. transaction_id_system: ' . $invoiceTrip->trip_id);
            }

            $successTwo = $trip->update([
                'status_payment' => Trip::TRIP_PAID,
            ]);
            //
            if (!$successTwo) {
                abort(500, 'Trip Update Failed');
            }


            // return 200 OK response // check abrham samson

            return "OK"; // means everything worked up, to reach this level of the code

        });

        return $var;
    }



}