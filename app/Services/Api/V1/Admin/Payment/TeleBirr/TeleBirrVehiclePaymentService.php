<?php

namespace App\Services\Api\V1\Admin\Payment\TeleBirr;

use SoapClient;
use Carbon\Carbon;
use App\Models\Order;
use SimpleXMLElement;
use App\Models\OrderUser;
use App\Models\InvoiceVehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Artisaninweb\SoapWrapper\SoapWrapper;

class TeleBirrVehiclePaymentService
{    

    // these values hold the RESPONSE Values From TeleBirr
    private $transactionIdSystemVal;
    private $transactionIdBanksVal;

    // B2C
    public function initiatePaymentToVehicle($transactionId, $amount, $reason, $receiverIdentifier)
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


Log::info('B2C TeleBirr Vehicle Payment (Payment to Vehicle): REQUEST we SENT : - ' . $soapRequestData);

  
            $client = new SoapClient(null, [
                'location' => $telebirrRequestUrl,
                'uri' => "http://schemas.xmlsoap.org/soap/envelope/",
                'trace' => 1
            ]);

            $responseXml = $client->__doRequest($soapRequestData, $telebirrRequestUrl, '', SOAP_1_1);

            Log::info('B2C TeleBirr Vehicle Payment (Payment to Vehicle): RESPONSE XML: - ' . $responseXml);

            $xmlResponse = simplexml_load_string($responseXml);


            // ----------------------------------------------------- READ THE XML Response From Telebirr ---------------------------------------------------------------------------------------------//

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
                        'message' => 'B2C TeleBirr Vehicle Payment (Payment to Vehicle) FAIL (Failed because Telebirr Responded Fault XML) - faultcode: ' . $faultCode . ', faultstring: ' . $faultString,
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
                Log::info("B2C TeleBirr Vehicle Payment (Payment to Vehicle): RESPONSE Converted to JSON" . json_encode($telebirrResponseParameters));


                // Check if the response code indicates success (You can define your own success code logic)
                if ($responseCode === '0') {
                    // This is the SUCCESSFUL response
                    Log::info('B2C TeleBirr Vehicle Payment (Payment to Vehicle) SUCCESS - ResponseCode: ' . $responseCode . ', ResponseDesc: ' . $responseDesc . ', OriginatorConversationID (transaction_id_system): ' . $transactionIdSystem . ', ConversationID (transaction_id_banks): ' . $transactionIdBanks);
                

                    $responseValue = $this->handlePaymentToVehicleAfterTeleBirrResponse();

                    if (!$responseValue->successful()) {
                        return response()->json(['message' => 'B2C TeleBirr Vehicle Payment (Payment to Vehicle) FAIL during handlePaymentToVehicleAfterTeleBirrResponse() Method, we use this method to handle SYSTEM logic after telebirr returns SUCCESS Response'], 500);
                    }

                    return response()->json([
                            'message' => 'B2C TeleBirr Vehicle Payment (Payment to Vehicle) SUCCESS.  ResponseCode: ' . $responseCode . ', ResponseDesc: ' . $responseDesc . ', OriginatorConversationID (transaction_id_system): ' . $transactionIdSystem . ', ConversationID (transaction_id_banks): ' . $transactionIdBanks,
                            'telebirr_response_parameters' => $telebirrResponseParameters,
                        ], 200);
                
                } 
                else if ($responseCode === '1001') {
                    Log::alert('B2C TeleBirr Vehicle Payment (Payment to Vehicle) FAIL due to Caller Authentication ERROR - ResponseCode: ' . $responseCode . ', ResponseDesc: ' . $responseDesc . ', OriginatorConversationID (transaction_id_system): ' . $transactionIdSystem . ', ConversationID (transaction_id_banks): ' . $transactionIdBanks);
                    
                    return response()->json(['message' => 'B2C TeleBirr Vehicle Payment (Payment to Vehicle) FAIL due to Caller Authentication ERROR - ResponseCode: ' . $responseCode . ', ResponseDesc: ' . $responseDesc . ', OriginatorConversationID (transaction_id_system): ' . $transactionIdSystem . ', ConversationID (transaction_id_banks): ' . $transactionIdBanks], 422);
                }
                else {
                    Log::alert('B2C TeleBirr Vehicle Payment (Payment to Vehicle) FAIL due to unknown response code. (ResponseCode is Neither 0 Nor 1001)');
                
                    return response()->json(['message' => 'B2C TeleBirr Vehicle Payment (Payment to Vehicle) FAIL due to unknown response code. (ResponseCode is Neither 0 Nor 1001)'], 422);
                }
            }
            else {
                Log::error('B2C TeleBirr Vehicle Payment (Payment to Vehicle) FAIL due to unknown ERRORs From TeleBirr Side. No valid xml or status code is found in TeleBirrs response');
            
                return response()->json(['message' => 'B2C TeleBirr Vehicle Payment (Payment to Vehicle) FAIL due to unknown ERRORs From TeleBirr Side. No valid xml or status code is found in TeleBirrs response'], 422);
            }


        } catch (\Exception $e) {
            // Handle exceptions
            Log::alert('B2C TeleBirr Vehicle Payment (Payment to Vehicle): FAIL, ERROR : - ' . $e->getMessage());

            return response()->json(['message' => 'B2C TeleBirr Vehicle Payment (Payment to Vehicle): FAIL, ERROR : - ' . $e->getMessage()], 422);
        }
    }










    ////////////////////////////// the following handles THE SYSTEM LOGIC after i get a RESPONSE from TELEBIRR   //////////////////////////////////////////////////
    
    public function handlePaymentToVehicleAfterTeleBirrResponse()
    {

        $transactionIdSystemValue =  $this->transactionIdSystemVal;     

        $var = DB::transaction(function () use ($transactionIdSystemValue) {
            
            // if paid status code from the bank is NOT 200 -> i will log and abort // abrham samson check
            // if paid status code from the bank is 200,  ->  I wil do the following // abrham samson check


            // $invoiceIdList = [];


            // Fetch all invoices where invoice_code matches the one from the request
            $invoiceVehicle = InvoiceVehicle::where('transaction_id_system', $transactionIdSystemValue)->first(); // this should NOT be exists().  this should be get(), because i am going to use actual data (records) of $invoices in the below foreach
            //
            if (!$invoiceVehicle) { 
                Log::alert('B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Vehicle Payment (Payment to Vehicle): the InvoiceVehicle with the transaction_id_system is not found. transaction_id_system' . $this->transactionIdSystemVal);
                abort(500, 'B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Vehicle Payment (Payment to Vehicle): the InvoiceVehicle with the transaction_id_system is not found. transaction_id_system' . $this->transactionIdSystemVal);
            }


            $this->updateInvoice($invoiceVehicle);


            $orderPrStatus = $this->getOrderPrStatus($invoiceVehicle);

            $this->updateOrder($invoiceVehicle, $orderPrStatus);

            



            // return 200 OK response // check abrham samson

            return response()->json(['message' => 'Success'], 200); // means everything worked up, to reach this level of the code

        });

        return $var;
    }





    







    private function getOrderPrStatus($invoiceVehicle)
    {
        // the fact of the matter we are using Order model only here
        // and NOT using OrderUser Model is, it is just to have the constants so it does not matter

        if ($invoiceVehicle->order_id !== null && $invoiceVehicle->order_user_id === null) {

            if ($invoiceVehicle->order->pr_status === Order::VEHICLE_PR_STARTED) {
                return Order::VEHICLE_PR_STARTED;
            } else if ($invoiceVehicle->order->pr_status === Order::VEHICLE_PR_LAST) {
                return Order::VEHICLE_PR_COMPLETED;
    
                        // this is no longer used since i am controlling it in invoice asking,
                        // which means in invoice asking i will prevent super_admin not ask another invoice for an order if there is an already UnPaid invoice for that order in invoices table
                        // $orderInvoicesPaymentCheck = Invoice::where('order_id', $invoice->order->id)
                        //                 ->where('status', Invoice::INVOICE_STATUS_NOT_PAID)
                        //                 ->get();
    
                        // if ($orderInvoicesPaymentCheck->isEmpty()) {
                        //     $orderPrStatus = Order::VEHICLE_PR_COMPLETED;
                        // } else {
                        //     $orderPrStatus = Order::VEHICLE_PR_LAST;
                        // }
    
            } else if ($invoiceVehicle->order->pr_status === Order::VEHICLE_PR_COMPLETED) {
                return Order::VEHICLE_PR_COMPLETED;
                        // CURRENTLY THIS WILL NOT HAPPEN BECAUSE , I AM HANDLING IT WHEN 'SUPER_ADMIN' ASKS PR
                            //
                            // i added this condition because (IN CASE I DID NOT HANDLE THIS CASE when PR IS ASKED BY 'SUPER_ADMIN' - the following may happen) 
                                    //
                                    // a multiple pr request can be made to the same order in consecutive timelines one after the other 
                                    // and from those invoices that are asked of the same order if the last invoice is asked of that order then the pr_status of the order would be PR_LAST
                                    // and if we pay any one of that order invoice, the order pr_status will be changed from PR_LAST to PR_COMPLETED
                                    // so when paying the rest of the invoices of that same order we must set the variable $orderPrStatus value (to PR_COMPLETED), even if the order shows PR_COMPLETED
                                    // this way we will have a variable to assign to the pr_status of order table as we did below (i.e = 'pr_status' => $orderPrStatus,)
    
            } else {
                Log::alert('TeleBirr callback: invalid order PR status for organization!. PR_STATUS: ' . $invoiceVehicle->order->pr_status);
                abort(422, 'TeleBirr callback: invalid order PR status for organization!. PR_STATUS: ' . $invoiceVehicle->order->pr_status);
            }

        }
        else if ($invoiceVehicle->order_id === null && $invoiceVehicle->order_user_id !== null) {

            if ($invoiceVehicle->order->pr_status === OrderUser::VEHICLE_PR_STARTED) {
                return OrderUser::VEHICLE_PR_STARTED;
            } else if ($invoiceVehicle->order->pr_status === OrderUser::VEHICLE_PR_LAST) {
                return OrderUser::VEHICLE_PR_COMPLETED;
    
                        // this is no longer used since i am controlling it in invoice asking,
                        // which means in invoice asking i will prevent super_admin not ask another invoice for an order if there is an already UnPaid invoice for that order in invoices table
                        // $orderInvoicesPaymentCheck = Invoice::where('order_id', $invoice->order->id)
                        //                 ->where('status', Invoice::INVOICE_STATUS_NOT_PAID)
                        //                 ->get();
    
                        // if ($orderInvoicesPaymentCheck->isEmpty()) {
                        //     $orderPrStatus = Order::VEHICLE_PR_COMPLETED;
                        // } else {
                        //     $orderPrStatus = Order::VEHICLE_PR_LAST;
                        // }
    
            } else if ($invoiceVehicle->order->pr_status === OrderUser::VEHICLE_PR_COMPLETED) {
                return OrderUser::VEHICLE_PR_COMPLETED;
                        // CURRENTLY THIS WILL NOT HAPPEN BECAUSE , I AM HANDLING IT WHEN 'SUPER_ADMIN' ASKS PR
                            //
                            // i added this condition because (IN CASE I DID NOT HANDLE THIS CASE when PR IS ASKED BY 'SUPER_ADMIN' - the following may happen) 
                                    //
                                    // a multiple pr request can be made to the same order in consecutive timelines one after the other 
                                    // and from those invoices that are asked of the same order if the last invoice is asked of that order then the pr_status of the order would be PR_LAST
                                    // and if we pay any one of that order invoice, the order pr_status will be changed from PR_LAST to PR_COMPLETED
                                    // so when paying the rest of the invoices of that same order we must set the variable $orderPrStatus value (to PR_COMPLETED), even if the order shows PR_COMPLETED
                                    // this way we will have a variable to assign to the pr_status of order table as we did below (i.e = 'pr_status' => $orderPrStatus,)
    
            } else {
                Log::alert('TeleBirr callback: invalid order PR status for organization!. PR_STATUS: ' . $invoiceVehicle->order->pr_status);
                abort(422, 'TeleBirr callback: invalid order PR status for organization!. PR_STATUS: ' . $invoiceVehicle->order->pr_status);
            }

        }
        else {
            // an invoice must have at least order_id or order_user_id, - - - -  other wise it will be the Following ERROR
            //
            return response()->json(['message' => 'B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Vehicle Payment (Payment to Vehicle): This Invoice Can NOT be Processed. Because: - this InvoiceVehicle does NOT have BOTH order_id or order_user_id'], 422);
        }

        

    }



    private function updateInvoice($invoiceVehicle)
    {
        $today = now()->format('Y-m-d');

        // commented because i have done it above
        // $invoiceVehicle = InvoiceVehicle::where('transaction_id_system', $this->transactionIdSystemVal)->first();
        // //
        // if (!$invoiceVehicle) {
        //     Log::alert('B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Vehicle Payment (Payment to Vehicle): the InvoiceVehicle with the transaction_id_system is not found. transaction_id_system' . $this->transactionIdSystemVal);
        //     abort(500, 'B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Vehicle Payment (Payment to Vehicle): the InvoiceVehicle with the transaction_id_system is not found. transaction_id_system' . $this->transactionIdSystemVal);
        // }

        // Update all invoices with the sent invoice_code
        $success = $invoiceVehicle->update([
            'status' => InvoiceVehicle::INVOICE_STATUS_PAID,
            'paid_date' => $today,
            'transaction_id_banks' => $this->transactionIdBanksVal,
        ]);
        // Handle invoice update failure
        if (!$success) {
            Log::alert('B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Vehicle Payment (Payment to Vehicle):  InvoiceVehicle Update Failed!');
            abort(500, 'B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Vehicle Payment (Payment to Vehicle):  InvoiceVehicle Update Failed!');
        }

    }


    private function updateOrder($invoiceVehicle, $orderPrStatus)
    {

        if ($invoiceVehicle->order_id !== null && $invoiceVehicle->order_user_id === null) {
            // this means the invoiceVehicle is from orders table (i.e. invoiceVehicle is from organization order) 
            //
            //
            // Check if the associated Order exists
            if (!$invoiceVehicle->order) {
                return response()->json(['message' => 'B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Vehicle Payment (Payment to Vehicle): Related order NOT found for this invoiceVehicle.'], 404);
            }

            // Update the order pr_status
            $successTwo = $invoiceVehicle->order()->update([
                'pr_status' => $orderPrStatus,
            ]);
            // Handle order update failure
            if (!$successTwo) {
                Log::alert('B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Vehicle Payment (Payment to Vehicle):  Order Update Failed');
                abort(500, 'B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Vehicle Payment (Payment to Vehicle):  Order Update Failed');
            }

        }
        else if ($invoiceVehicle->order_id === null && $invoiceVehicle->order_user_id !== null) {
            // this means the invoiceVehicle is from order_users table (i.e. invoiceVehicle is from individual customer order) 
            //
            //
            // Check if the associated OrderUser exists
            if (!$invoiceVehicle->orderUser) {
                return response()->json(['message' => 'Related orderUser NOT found for this invoice.'], 404);
            }

            // Update the orderUser pr_status
            $successTwo = $invoiceVehicle->orderUser()->update([
                'pr_status' => $orderPrStatus,
            ]);
            // Handle orderUser update failure
            if (!$successTwo) {
                Log::alert('B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Vehicle Payment (Payment to Vehicle):  Order Update Failed');
                abort(500, 'B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Vehicle Payment (Payment to Vehicle):  Order Update Failed');
            }            

        }
        else {
            // an invoice must have at least order_id or order_user_id, - - - -  other wise it will be the Following ERROR
            //
            return response()->json(['message' => 'B2C TeleBirr (AFTER TELEBIRR RESPONSE) - Vehicle Payment (Payment to Vehicle): This Invoice Can NOT be Processed. Because: - this InvoiceVehicle does NOT have BOTH order_id or order_user_id'], 422);
        }
        
    }

}