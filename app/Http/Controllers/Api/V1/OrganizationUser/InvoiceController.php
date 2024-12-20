<?php

namespace App\Http\Controllers\Api\V1\OrganizationUser;

use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\OrganizationUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\OrganizationUserRequests\PayInvoiceRequest;
use App\Http\Requests\Api\V1\OrganizationUserRequests\StoreInvoiceRequest;
use App\Http\Requests\Api\V1\OrganizationUserRequests\UpdateInvoiceRequest;
use App\Http\Resources\Api\V1\InvoiceResources\InvoiceForOrganizationResource;
use App\Services\Api\V1\OrganizationUser\Payment\BOA\BOAOrganizationPaymentService;
use App\Http\Requests\Api\V1\OrganizationUserRequests\PayInvoicesCallbackTelebirrRequest;
use App\Services\Api\V1\OrganizationUser\Payment\TeleBirr\TeleBirrOrganizationPaymentService;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {        
        /* $validatedData = */ $request->validate([
            'invoice_status_search' => [
                'sometimes', 'string', Rule::in([Invoice::INVOICE_STATUS_NOT_PAID, Invoice::INVOICE_STATUS_PAID]),
            ],
            // Other validation rules if needed
        ]);

        $user = auth()->user();
        $organizationUser = OrganizationUser::find($user->id);

        $invoices = Invoice::where('organization_id', $organizationUser->organization_id);

        // use Filtering service OR Scope to do this
        if ($request->has('order_id_search')) {
            if (isset($request['order_id_search'])) {
                $orderId = $request['order_id_search'];

                $invoices = $invoices->where('order_id', $orderId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('invoice_code_search')) {
            if (isset($request['invoice_code_search'])) {
                $invoiceCode = $request['invoice_code_search'];

                $invoices = $invoices->where('invoice_code', $invoiceCode);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            } 
        }
        if ($request->has('invoice_status_search')) {
            if (isset($request['invoice_status_search'])) {
                $invoiceStatus = $request['invoice_status_search'];

                $invoices = $invoices->where('status', $invoiceStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }

        }

        

        $invoiceData = $invoices->with('order')->latest()->paginate(FilteringService::getPaginate($request));

        return InvoiceForOrganizationResource::collection($invoiceData);
    }



    /**
     * Display a listing of the resource.
     * 
     * But Filtered by      invoice_code = invoice_code_search ,      status = NOT_PAID ,      and      paid_date = NULL
     * also filtered by OrganizationId of the logged in user
     * 
     * it is used when the organization INTENDS TO PAY invoices with invoice code
     * 
     * THIS ONE IS USED WHEN ORGANIZATION WANTS TO SEE ALL INVOICES BASED ON INVOICE - INTENDING TO PAY
     * 
     * CAN ONLY see the UNPAID invoices of an invoice_code (NOT PAID invoices of that invoice_code)
     * 
     */
    public function indexByInvoiceCode(Request $request)
    {
        $user = auth()->user();
        $organizationUser = OrganizationUser::find($user->id);

        $invoices = Invoice::where('organization_id', $organizationUser->organization_id);

        // use Filtering service OR Scope to do this
        if ($request->has('invoice_code_search')) {
            if (isset($request['invoice_code_search'])) {
                $invoiceCode = $request['invoice_code_search'];

                $invoices = $invoices->where('invoice_code', $invoiceCode)
                    ->where('status', Invoice::INVOICE_STATUS_NOT_PAID)
                    ->where('paid_date', null);

                $invoiceData = $invoices->with('order')->latest()->get();

                // $totalPriceAmount now contains the total price_amount of all invoices with the specified 'invoice_code' , status unpaid and paid_date null // it will do add all invoices with the specified invoice_code (that are not paid and have null paid date)
                $totalPriceAmount = Invoice::where('invoice_code', $invoiceCode)
                    ->where('organization_id', $organizationUser->organization_id)
                    ->where('status', Invoice::INVOICE_STATUS_NOT_PAID)
                    ->where('paid_date', null)
                    ->sum('price_amount');

                return response()->json(
                    [
                        'price_amount_total' => $totalPriceAmount,
                        'invoice_code_requested_value' => $invoiceCode,
                        'data' => InvoiceForOrganizationResource::collection($invoiceData),
                    ],
                    200
                );
            } 
            else {
                return response()->json(['message' => 'Required parameter "invoice_code_search" is empty or Value Not Set'], 400);
            } 
        }
        else {
            return response()->json(['message' => 'Required parameter "invoice_code_search" is missing'], 400);
        } 
        
    }



    /**
     * Store a newly created resource in storage.
     * 
     * this endpoint method is to pay only with invoice_code
     * ALL the invoice_ids sent to me here MUST Belong to the SAME invoice_code , otherwise i will return ERROR
     * 
     * after everything is checked and verified 
     * I will pass the invoice_code to the bank and
     * after the payment is completed , when my callback endpoint is called by the banks, the banks will send me back the invoice_code
     * i will use that invoice_code to update all the invoices under it (i.e to status = PAID , paid_date = today()  ,  and also change the pr_status of the parent orders of those invoices accordingly i.e $invoice->order->pr_status = some status)
     * 
     * 
     * to do this the second foreach statement i put here must be moved to be under the callback endpoint
     * so all the operations on the second foreach must be done under the callback endpoint that the banks call after the payment is complete
     * 
     */
    public function payInvoices(PayInvoiceRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {



            if ($request->has('invoices')) {

                $invoiceIds = collect($request->invoices)->pluck('invoice_id');
                // $invoiceIdsVal = $request->input('invoices.*.invoice_id'); // check if this works
                //
                //
                // Check if all invoices have the same invoice_code            // a PR payment request for an invoice or multiple invoices should be sent for invoices that belong to only one invoice_code
                $invoiceCodes = Invoice::whereIn('id', $invoiceIds)->pluck('invoice_code')->unique();
                if ($invoiceCodes->count() !== 1) {
                    return response()->json(['message' => 'All invoices must belong to the same invoice code.'], 422);
                }
                // Now we are sure all the invoices in the invoice request belong to one invoice_code
                // So let's get that one invoice_code      // it is worth to mention that the following collection only have one invoice_code
                $invoiceCode = $invoiceCodes->first(); // Retrieves the first invoice_code FROM our collection which in fact at this stage have ONLY one invoice_code


                //


                // Check if all invoices have the same organization_id            // a PR payment request or multiple PR payment request should be sent for only one organization at a time
                $organizationIds = Invoice::whereIn('id', $invoiceIds)->pluck('organization_id')->unique();
                if ($organizationIds->count() > 1) {
                    return response()->json(['message' => 'All invoices must belong to the same organization.'], 422);
                }
                if ($organizationIds->count() < 1) {
                    return response()->json(['message' => 'no valid organization_id for the invoice.'], 422);
                }
                // Now we are sure all the invoices in the invoice request belong to one organization
                // So let's get that one organization_id      // it is worth to mention that the following collection only have one organization_id
                $organizationId = $organizationIds->first(); // Retrieves the first organization_id FROM our collection which in fact at this stage have ONLY one organization_id  

                // get the logged in organization User
                $user = auth()->user();
                $organizationUser = OrganizationUser::find($user->id);


                // check if the organization_id of the orders is similar with the logged in organizationUser Organization_id
                if ($organizationUser->organization_id != $organizationId) {
                    // this order is NOT be owned by the organization that the order requester belongs in // so i return error and abort
                    return response()->json(['message' => 'invalid Order is selected or Requested. or One of the requested Invoice is not found. Deceptive request Aborted.'], 403);
                }


                //  check if there is duplicate invoice_id in the JSON and if there Duplicate invoice_id is return ERROR
                //
                if ($invoiceIds->count() !== $invoiceIds->unique()->count()) {
                    return response()->json(['message' => 'Duplicate invoice_id values are not allowed.'], 400);
                }
                // Continue processing the request if no duplicate invoice_id values are found


                // compare the number of invoices in the request   ,   with that of the database(that have invoice_code = $invoiceCode) 
                $countInvoiceIds = Invoice::where('invoice_code', $invoiceCode)->count();

                if ($countInvoiceIds !== $invoiceIds->unique()->count()) {
                    return response()->json(['message' => 'the number of invoice ids sent from the request should be equal to the number of invoice ids in the database with invoice_code: '. $invoiceCode], 400);
                }













                /*
                    now below i will to check 
                    1. all the ids from the invoice table with the invoice_code = $invoiceCode must exist in the invoice_ids that are sent in the request
                    2. all the invoice_id values in the request must exist in the invoice table
                */
                

                /* 
                    // METHOD ONE , this WORKS but it is more detailed so it is longer and complicated
                
                        // Retrieve all invoice_id values from the invoices table where invoice_code is $invoiceCode
                        $databaseInvoiceIds = Invoice::where('invoice_code', $invoiceCode)->pluck('id')->toArray();

                        // Get the invoice_id values from the request (assuming the request is in the $request variable)
                        $requestInvoiceIds = collect($request->invoices)->pluck('invoice_id');

                        // Check if all the database invoice ids exist in the request invoice_ids
                        $allDatabaseIdsExistInRequest = collect($databaseInvoiceIds)->intersect($requestInvoiceIds)->count() === count($databaseInvoiceIds);

                        // Check if all the request invoice_ids exist in the database invoice ids
                        $allRequestIdsExistInDatabase = collect($requestInvoiceIds)->intersect($databaseInvoiceIds)->count() === count($requestInvoiceIds);

                        if (!$allDatabaseIdsExistInRequest) {
                            return response()->json([
                                'message' => 'All invoice IDs from the database that have the invoice_code: ' . $invoiceCode . ' should be included in your payment request.'
                            ], 400);
                        }

                        if (!$allRequestIdsExistInDatabase) {
                            return response()->json([
                                'message' => 'All invoice IDs included in your payment request should be under invoice_code: ' . $invoiceCode . ', or there are invoice_ids in the request that are not present in the database for the given invoice_code'
                            ], 400);
                        }
                    //
                    // end METHOD ONE
                */
                


                // METHOD TWO , WORKS and it is short and preside and NOT complected, might consume resources though
                //
                // Extract the invoice IDs from the nested structure in the request using Arr::pluck()
                $invoiceIdsFromRequestArr = Arr::pluck($request->input('invoices'), 'invoice_id');
                //
                // Extract the invoice IDs from the nested structure in the request using collection methods
                // $invoiceIdsFromRequestCollection = collect($request->invoices)->pluck('invoice_id'); // this works also

                $invoiceIdsFromDatabase = Invoice::where('invoice_code', $invoiceCode)->pluck('id')->all();

                // Sort the arrays before comparing to ensure order doesn't affect the comparison
                sort($invoiceIdsFromRequestArr);
                sort($invoiceIdsFromDatabase);

                if ($invoiceIdsFromRequestArr !== $invoiceIdsFromDatabase) {
                    return response()->json([
                        'message' => 'All invoice IDs included in your payment request should be Exactly equals with All invoice IDs from the database that have the invoice_code: ' . $invoiceCode
                    ], 400);
                }
                //
                // end METHOD TWO
                


                // METHOD THREE, This version efficiently compares the arrays lengths and elements without sorting, providing a more optimized solution in terms of resource usage
                //
                // Extract the invoice IDs from the nested structure in the request using Arr::pluck()
                // $invoiceIdsFromRequestArr = Arr::pluck($request->input('invoices'), 'invoice_id');

                // // Get the invoice IDs from the database for the given invoice code
                // $invoiceIdsFromDatabase = Invoice::where('invoice_code', $invoiceCode)->pluck('id')->all();

                // // Compare both arrays to find differences
                // $diffInRequest = array_diff($invoiceIdsFromRequestArr, $invoiceIdsFromDatabase);
                // $diffInDatabase = array_diff($invoiceIdsFromDatabase, $invoiceIdsFromRequestArr);

                // // Check if both arrays have exactly the same values
                // if (count($diffInRequest) > 0 || count($diffInDatabase) > 0) {
                //     return response()->json([
                //         'message' => 'All invoice IDs in the request should exactly match the invoice IDs from the database for invoice code: ' . $invoiceCode
                //     ], 400);
                // }
                // end METHOD THREE















                // todays date
                $today = now()->format('Y-m-d');






                // this foreach is to check for every validation and error handling BEFORE diving in to the second foreach and doing the actual operation
                // i used this approach so that      i would not ABORT in the second foreach when ERROR is found in the middle of doing the actual operation, // it is to decrease data inconsistency caused when handling errors in the second foreach
                foreach ($request->safe()->invoices as $requestData) {

                    $invoice = Invoice::find($requestData['invoice_id']);

                    // Check if the associated Order exists
                    if (!$invoice->order) {
                        return response()->json(['message' => 'Related order not found for this invoice.'], 404);
                    }


                    // lets check the order pr_status
                    if ($invoice->order->pr_status === null) {
                        return response()->json(['message' => 'we check the parent Order of this invoice: ' . $invoice->id . ' , and PR have not been started for this order yet. order: ' . $invoice->order->id . ' , The order have pr_status NULL.'], 500); // this scenario will NOT happen
                    }
                    if ($invoice->order->pr_status === Order::ORDER_PR_COMPLETED) {
                        return response()->json(['message' => 'we check the parent Order of this invoice: ' . $invoice->id . ' , and all PR is paid for this order: ' . $invoice->order->id . ' , The order have PR_COMPLETED status.'], 409);
                    }
                    if ($invoice->order->pr_status === Order::ORDER_PR_TERMINATED) {
                        return response()->json(['message' => 'we check the parent Order of this invoice: ' . $invoice->id . ' , and this order PR is terminated for some reason. please check with the organization and Super Admin why PR is terminated. order: ' . $invoice->order->id . ' , The order have PR_TERMINATED status.'], 410);
                    }

                    // check if the actual invoice is Paid // if the this invoice have status = PAID
                    if ($invoice->status === Invoice::INVOICE_STATUS_PAID) {
                        return response()->json(['message' => 'This Invoice is Already Paid.  Invoice: ' . $invoice->id . ' , The Invoice have PAID status.'], 409);
                    }
                    if ($invoice->paid_date !== null) {
                        return response()->json(['message' => 'This Invoice is Already Paid.  Invoice: ' . $invoice->id . ' , The Invoice have value in its paid date.'], 409);
                    }



                }


                // we are just updating te payment method sent in the request for all invoices sent in the request
                foreach ($request->safe()->invoices as $requestData) {

                    $invoice = Invoice::find($requestData['invoice_id']);

                    $success = $invoice->update([
                        'payment_method' => $request['payment_method'],
                    ]);
                    //
                    if (!$success) {
                        return response()->json(['message' => 'Invoice Update Failed'], 500);
                    }

                }

                // check abrham samson
                // do something here to handle = if the actual associated ORDER of EACH requested INVOICE does NOT exit     
                // should be checked separately using foreach or another method to handle those separately

                


                // compare actual total price from database with the sent total price from frontend
                // but check if this woks perfect // check abrham samson
                $totalPriceAmount = Invoice::whereIn('id', $invoiceIds)
                    ->where('status', Invoice::INVOICE_STATUS_NOT_PAID)
                    ->where('paid_date', null)
                    ->sum('price_amount');


                $totalPriceAmountByInvoiceCode = Invoice::where('invoice_code', $invoiceCode)
                    ->sum('price_amount');
                    

                $totalPriceAmountFromRequest = (int) $requestData['price_amount_total'];


                if ($totalPriceAmount !== $totalPriceAmountFromRequest || 
                    $totalPriceAmount !== $totalPriceAmountByInvoiceCode || 
                    $totalPriceAmountFromRequest !== $totalPriceAmountByInvoiceCode) {
                    return response()->json(['message' => 'The total prices do not match between the request and actual database calculations.'], 422);
                }





                 /* START Payment Service Call */

                // do the actual payment 
                // pass the $totalPriceAmount so that the customer could pay it
                // pass the $invoiceCode so that it could be used in the callback endpoint
                
                if ($request['payment_method'] == Invoice::INVOICE_BOA) {

                    $boaOrganizationPaymentService = new BOAOrganizationPaymentService();
                    $valuePaymentRenderedView = $boaOrganizationPaymentService->initiatePaymentForPR($totalPriceAmount, $invoiceCode);

                    return $valuePaymentRenderedView;
                }
                else if ($request['payment_method'] == Invoice::INVOICE_TELE_BIRR) {

                    // $boaOrganizationPaymentService = new BOAOrganizationPaymentService();
                    // $valuePaymentRenderedView = $boaOrganizationPaymentService->initiatePaymentForPR($totalPriceAmount, $invoiceCode);

                    // return $valuePaymentRenderedView;

                    $teleBirrOrganizationPaymentService = new TeleBirrOrganizationPaymentService();
                    $valuePayment = $teleBirrOrganizationPaymentService->createOrder($invoiceCode, $totalPriceAmount)/* initiatePaymentForPR($invoiceCode, $totalPriceAmount) */;

                    return $valuePayment; 

                }
                else {
                    return response()->json(['error' => 'Invalid payment method selected.'], 422);
                }
                

                 /* END Payment Service Call */







                /*
                // the following code should be moved to the callback endpoint
                // so all the operations on the second foreach must be done under the callback endpoint that the banks call after the payment is complete

                $invoiceIdList = [];

                // Now We are sure all the impurities are filtered in the above foreach
                // So do the ACTUAL Operations on each of the invoices sent in the request
                foreach ($request->safe()->invoices as $requestData) {

                    $invoice = Invoice::find($requestData['invoice_id']);

                    if ($invoice->order->pr_status === Order::ORDER_PR_STARTED) {
                        $orderPrStatus = Order::ORDER_PR_STARTED;
                    }
                    else if ($invoice->order->pr_status === Order::ORDER_PR_LAST) {

                        $orderInvoicesPaymentCheck = Invoice::where('order_id', $invoice->order->id)
                                    ->where('status', Invoice::INVOICE_STATUS_NOT_PAID)
                                    ->get();

                        if ($orderInvoicesPaymentCheck->isEmpty()) {
                            $orderPrStatus = Order::ORDER_PR_COMPLETED;
                        } else {
                            $orderPrStatus = Order::ORDER_PR_LAST;
                        }

                    }
                    else if ($invoice->order->pr_status === Order::ORDER_PR_COMPLETED) { 
                        // i added this condition because a multiple pr request can be made to the same order in consecutive timelines one after the other 
                        // and from those invoices that are asked of the same order if the last invoice is asked of that order then the pr_status of the order would be PR_LAST
                        // and if we pay any one of that order invoice, the order pr_status will be changed from PR_LAST to PR_COMPLETED
                        // so when paying the rest of the invoices of that same order we must set the variable $orderPrStatus value (to PR_COMPLETED), even if the order shows PR_COMPLETED
                        // this way we will have a variable to assign to the pr_status of order table as we did below (i.e = 'pr_status' => $orderPrStatus,)
                        $orderPrStatus = Order::ORDER_PR_COMPLETED;
                    }


                    $success = $invoice->update([
                        'status' => Invoice::INVOICE_STATUS_PAID,
                        'paid_date' => $today,
                    ]);
                    //
                    if (!$success) {
                        return response()->json(['message' => 'Invoice Update Failed'], 500);
                    }



                    $successTwo = $invoice->order()->update([
                        'pr_status' => $orderPrStatus,
                    ]);
                    //
                    if (!$successTwo) {
                        return response()->json(['message' => 'Order Update Failed'], 500);
                    }


                    // $invoiceIdList[] = Invoice::find($invoice->id); // consumes more resource

                    $invoiceIdList[] = $invoice->id; // USED
                

                }




                // this get all the invoices updated above
                $invoicesData = Invoice::whereIn('id', $invoiceIdList)->with('order')->latest()->get();   
                return InvoiceForOrganizationResource::collection($invoicesData);
                */

            }


        });

        return $var;
    }








    public function payInvoicesNewOpenRouteGet(PayInvoiceRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {



            if ($request->has('invoices')) {

                $invoiceIds = collect($request->invoices)->pluck('invoice_id');
                // $invoiceIdsVal = $request->input('invoices.*.invoice_id'); // check if this works
                //
                //
                // Check if all invoices have the same invoice_code            // a PR payment request for an invoice or multiple invoices should be sent for invoices that belong to only one invoice_code
                $invoiceCodes = Invoice::whereIn('id', $invoiceIds)->pluck('invoice_code')->unique();
                if ($invoiceCodes->count() !== 1) {
                    return response()->json(['message' => 'All invoices must belong to the same invoice code.'], 422);
                }
                // Now we are sure all the invoices in the invoice request belong to one invoice_code
                // So let's get that one invoice_code      // it is worth to mention that the following collection only have one invoice_code
                $invoiceCode = $invoiceCodes->first(); // Retrieves the first invoice_code FROM our collection which in fact at this stage have ONLY one invoice_code


                //


                // Check if all invoices have the same organization_id            // a PR payment request or multiple PR payment request should be sent for only one organization at a time
                $organizationIds = Invoice::whereIn('id', $invoiceIds)->pluck('organization_id')->unique();
                if ($organizationIds->count() > 1) {
                    return response()->json(['message' => 'All invoices must belong to the same organization.'], 422);
                }
                if ($organizationIds->count() < 1) {
                    return response()->json(['message' => 'no valid organization_id for the invoice.'], 422);
                }
                // Now we are sure all the invoices in the invoice request belong to one organization
                // So let's get that one organization_id      // it is worth to mention that the following collection only have one organization_id
                $organizationId = $organizationIds->first(); // Retrieves the first organization_id FROM our collection which in fact at this stage have ONLY one organization_id  

                // get the logged in organization User
                // $user = auth()->user();
                $organizationUser = OrganizationUser::find($request['organization_user_id']);


                // check if the organization_id of the orders is similar with the logged in organizationUser Organization_id
                if ($organizationUser->organization_id != $organizationId) {
                    // this order is NOT be owned by the organization that the order requester belongs in // so i return error and abort
                    return response()->json(['message' => 'invalid Order is selected or Requested. or One of the requested Invoice is not found. Deceptive request Aborted.'], 403);
                }


                //  check if there is duplicate invoice_id in the JSON and if there Duplicate invoice_id is return ERROR
                //
                if ($invoiceIds->count() !== $invoiceIds->unique()->count()) {
                    return response()->json(['message' => 'Duplicate invoice_id values are not allowed.'], 400);
                }
                // Continue processing the request if no duplicate invoice_id values are found


                // compare the number of invoices in the request   ,   with that of the database(that have invoice_code = $invoiceCode) 
                $countInvoiceIds = Invoice::where('invoice_code', $invoiceCode)->count();

                if ($countInvoiceIds !== $invoiceIds->unique()->count()) {
                    return response()->json(['message' => 'the number of invoice ids sent from the request should be equal to the number of invoice ids in the database with invoice_code: '. $invoiceCode], 400);
                }













                /*
                    now below i will to check 
                    1. all the ids from the invoice table with the invoice_code = $invoiceCode must exist in the invoice_ids that are sent in the request
                    2. all the invoice_id values in the request must exist in the invoice table
                */
                

                /* 
                    // METHOD ONE , this WORKS but it is more detailed so it is longer and complicated
                
                        // Retrieve all invoice_id values from the invoices table where invoice_code is $invoiceCode
                        $databaseInvoiceIds = Invoice::where('invoice_code', $invoiceCode)->pluck('id')->toArray();

                        // Get the invoice_id values from the request (assuming the request is in the $request variable)
                        $requestInvoiceIds = collect($request->invoices)->pluck('invoice_id');

                        // Check if all the database invoice ids exist in the request invoice_ids
                        $allDatabaseIdsExistInRequest = collect($databaseInvoiceIds)->intersect($requestInvoiceIds)->count() === count($databaseInvoiceIds);

                        // Check if all the request invoice_ids exist in the database invoice ids
                        $allRequestIdsExistInDatabase = collect($requestInvoiceIds)->intersect($databaseInvoiceIds)->count() === count($requestInvoiceIds);

                        if (!$allDatabaseIdsExistInRequest) {
                            return response()->json([
                                'message' => 'All invoice IDs from the database that have the invoice_code: ' . $invoiceCode . ' should be included in your payment request.'
                            ], 400);
                        }

                        if (!$allRequestIdsExistInDatabase) {
                            return response()->json([
                                'message' => 'All invoice IDs included in your payment request should be under invoice_code: ' . $invoiceCode . ', or there are invoice_ids in the request that are not present in the database for the given invoice_code'
                            ], 400);
                        }
                    //
                    // end METHOD ONE
                */
                




                // METHOD TWO , WORKS and it is short and preside and NOT complected, might consume resources though
                //
                // Extract the invoice IDs from the nested structure in the request using Arr::pluck()
                $invoiceIdsFromRequestArr = Arr::pluck($request->input('invoices'), 'invoice_id');
                //
                // Extract the invoice IDs from the nested structure in the request using collection methods
                // $invoiceIdsFromRequestCollection = collect($request->invoices)->pluck('invoice_id'); // this works also

                $invoiceIdsFromDatabase = Invoice::where('invoice_code', $invoiceCode)->pluck('id')->all();

                

                // SORT the ARRAYs before comparing to ensure ORDER doesn't affect the comparison 
                //      ||
                //      \/


                // NOT Working
                /*
                // ERROR = String vs Int
                sort($invoiceIdsFromRequestArr);
                sort($invoiceIdsFromDatabase);
                // 
                dd(json_encode($invoiceIdsFromRequestArr) . ' and ' . json_encode($invoiceIdsFromDatabase));
                // this will get us the below value
                //      // i.e.  "["2","3"] and [2,3]"    -   ERROR = even if the contents of both values are the same the double quotes on the first value andd the missing double quotes on the second value will make them NOT equal
                //                  //
                //                  // and i am getting this, i think this is why it is catching it as an ERROR,, because the first set of ids are wrapped in double quotes and the second are NOT
                */


                // FIX
                //      // The issue you are facing is related to the comparison of two arrays in your PHP code. One array is created using Arr::pluck and is serialized with double quotes around the values, while the other array is obtained from the database and is not serialized in the same way. This difference in serialization is causing the arrays to not match when compared.
                //      // To resolve this issue and ensure that the comparison between the arrays is accurate, you can normalize the arrays before comparing them. One way to achieve this is by converting both arrays to a common format before the comparison. Here's a revised version of your code that normalizes the arrays before comparing them:
                //      // In this revised code snippet, both arrays are normalized by converting all values to integers using array_map('intval', ...). This step ensures that all values in both arrays are of the same type before comparison. Sorting the arrays after normalization helps in ensuring that the order of elements does not affect the comparison.
                //      // By normalizing and sorting both arrays in this manner, you can compare them accurately and avoid issues arising from differences in serialization or data types.
                //
                //
                // WORKING
                //
                // Normalize the arrays by converting all values to integers
                $invoiceIdsFromRequestArrIntegerValue = array_map('intval', $invoiceIdsFromRequestArr);
                $invoiceIdsFromDatabaseIntegerValue = array_map('intval', $invoiceIdsFromDatabase);

                // Sort the arrays make order of the contents Similar
                sort($invoiceIdsFromRequestArrIntegerValue);
                sort($invoiceIdsFromDatabaseIntegerValue);


                dd(json_encode($invoiceIdsFromRequestArr) . ' and ' . json_encode($invoiceIdsFromDatabase));
                // this will get us the below value
                //      // i.e.      -    WORKING



                if ($invoiceIdsFromRequestArrIntegerValue !== $invoiceIdsFromDatabaseIntegerValue) {
                    return response()->json([
                        'message' => 'All invoice IDs included in your payment request should be Exactly equals with All invoice IDs from the database that have the invoice_code: ' . $invoiceCode
                    ], 400);
                }
                //
                // end METHOD TWO
                




                // METHOD THREE, This version efficiently compares the arrays lengths and elements without sorting, providing a more optimized solution in terms of resource usage
                //
                // Extract the invoice IDs from the nested structure in the request using Arr::pluck()
                // $invoiceIdsFromRequestArr = Arr::pluck($request->input('invoices'), 'invoice_id');

                // // Get the invoice IDs from the database for the given invoice code
                // $invoiceIdsFromDatabase = Invoice::where('invoice_code', $invoiceCode)->pluck('id')->all();

                // // Compare both arrays to find differences
                // $diffInRequest = array_diff($invoiceIdsFromRequestArr, $invoiceIdsFromDatabase);
                // $diffInDatabase = array_diff($invoiceIdsFromDatabase, $invoiceIdsFromRequestArr);

                // // Check if both arrays have exactly the same values
                // if (count($diffInRequest) > 0 || count($diffInDatabase) > 0) {
                //     return response()->json([
                //         'message' => 'All invoice IDs in the request should exactly match the invoice IDs from the database for invoice code: ' . $invoiceCode
                //     ], 400);
                // }
                // end METHOD THREE















                // todays date
                $today = now()->format('Y-m-d');






                // this foreach is to check for every validation and error handling BEFORE diving in to the second foreach and doing the actual operation
                // i used this approach so that      i would not ABORT in the second foreach when ERROR is found in the middle of doing the actual operation, // it is to decrease data inconsistency caused when handling errors in the second foreach
                foreach ($request->safe()->invoices as $requestData) {

                    $invoice = Invoice::find($requestData['invoice_id']);

                    // Check if the associated Order exists
                    if (!$invoice->order) {
                        return response()->json(['message' => 'Related order not found for this invoice.'], 404);
                    }


                    // lets check the order pr_status
                    if ($invoice->order->pr_status === null) {
                        return response()->json(['message' => 'we check the parent Order of this invoice: ' . $invoice->id . ' , and PR have not been started for this order yet. order: ' . $invoice->order->id . ' , The order have pr_status NULL.'], 500); // this scenario will NOT happen
                    }
                    if ($invoice->order->pr_status === Order::ORDER_PR_COMPLETED) {
                        return response()->json(['message' => 'we check the parent Order of this invoice: ' . $invoice->id . ' , and all PR is paid for this order: ' . $invoice->order->id . ' , The order have PR_COMPLETED status.'], 409);
                    }
                    if ($invoice->order->pr_status === Order::ORDER_PR_TERMINATED) {
                        return response()->json(['message' => 'we check the parent Order of this invoice: ' . $invoice->id . ' , and this order PR is terminated for some reason. please check with the organization and Super Admin why PR is terminated. order: ' . $invoice->order->id . ' , The order have PR_TERMINATED status.'], 410);
                    }

                    // check if the actual invoice is Paid // if the this invoice have status = PAID
                    if ($invoice->status === Invoice::INVOICE_STATUS_PAID) {
                        return response()->json(['message' => 'This Invoice is Already Paid.  Invoice: ' . $invoice->id . ' , The Invoice have PAID status.'], 409);
                    }
                    if ($invoice->paid_date !== null) {
                        return response()->json(['message' => 'This Invoice is Already Paid.  Invoice: ' . $invoice->id . ' , The Invoice have value in its paid date.'], 409);
                    }



                }


                // we are just updating te payment method sent in the request for all invoices sent in the request
                foreach ($request->safe()->invoices as $requestData) {

                    $invoice = Invoice::find($requestData['invoice_id']);

                    $success = $invoice->update([
                        'payment_method' => $request['payment_method'],
                    ]);
                    //
                    if (!$success) {
                        return response()->json(['message' => 'Invoice Update Failed'], 500);
                    }

                }

                // check abrham samson
                // do something here to handle = if the actual associated ORDER of EACH requested INVOICE does NOT exit     
                // should be checked separately using foreach or another method to handle those separately

                


                // compare actual total price from database with the sent total price from frontend
                // but check if this woks perfect // check abrham samson
                $totalPriceAmount = Invoice::whereIn('id', $invoiceIds)
                    ->where('status', Invoice::INVOICE_STATUS_NOT_PAID)
                    ->where('paid_date', null)
                    ->sum('price_amount');


                $totalPriceAmountByInvoiceCode = Invoice::where('invoice_code', $invoiceCode)
                    ->sum('price_amount');
                    

                $totalPriceAmountFromRequest = (int) $requestData['price_amount_total'];


                if ($totalPriceAmount !== $totalPriceAmountFromRequest || 
                    $totalPriceAmount !== $totalPriceAmountByInvoiceCode || 
                    $totalPriceAmountFromRequest !== $totalPriceAmountByInvoiceCode) {
                    return response()->json(['message' => 'The total prices do not match between the request and actual database calculations.'], 422);
                }





                 /* START Payment Service Call */

                // do the actual payment 
                // pass the $totalPriceAmount so that the customer could pay it
                // pass the $invoiceCode so that it could be used in the callback endpoint
                
                if ($request['payment_method'] == Invoice::INVOICE_BOA) {

                    $boaOrganizationPaymentService = new BOAOrganizationPaymentService();
                    $valuePaymentRenderedView = $boaOrganizationPaymentService->initiatePaymentForPR($totalPriceAmount, $invoiceCode);

                    return $valuePaymentRenderedView;
                }
                else if ($request['payment_method'] == Invoice::INVOICE_TELE_BIRR) {

                    // $boaOrganizationPaymentService = new BOAOrganizationPaymentService();
                    // $valuePaymentRenderedView = $boaOrganizationPaymentService->initiatePaymentForPR($totalPriceAmount, $invoiceCode);

                    // return $valuePaymentRenderedView;

                    $teleBirrOrganizationPaymentService = new TeleBirrOrganizationPaymentService();
                    $valuePayment = $teleBirrOrganizationPaymentService->createOrder($invoiceCode, $totalPriceAmount)/* initiatePaymentForPR($invoiceCode, $totalPriceAmount) */;

                    return $valuePayment; 

                }
                else {
                    return response()->json(['error' => 'Invalid payment method selected.'], 422);
                }
                

                 /* END Payment Service Call */







                /*
                // the following code should be moved to the callback endpoint
                // so all the operations on the second foreach must be done under the callback endpoint that the banks call after the payment is complete

                $invoiceIdList = [];

                // Now We are sure all the impurities are filtered in the above foreach
                // So do the ACTUAL Operations on each of the invoices sent in the request
                foreach ($request->safe()->invoices as $requestData) {

                    $invoice = Invoice::find($requestData['invoice_id']);

                    if ($invoice->order->pr_status === Order::ORDER_PR_STARTED) {
                        $orderPrStatus = Order::ORDER_PR_STARTED;
                    }
                    else if ($invoice->order->pr_status === Order::ORDER_PR_LAST) {

                        $orderInvoicesPaymentCheck = Invoice::where('order_id', $invoice->order->id)
                                    ->where('status', Invoice::INVOICE_STATUS_NOT_PAID)
                                    ->get();

                        if ($orderInvoicesPaymentCheck->isEmpty()) {
                            $orderPrStatus = Order::ORDER_PR_COMPLETED;
                        } else {
                            $orderPrStatus = Order::ORDER_PR_LAST;
                        }

                    }
                    else if ($invoice->order->pr_status === Order::ORDER_PR_COMPLETED) { 
                        // i added this condition because a multiple pr request can be made to the same order in consecutive timelines one after the other 
                        // and from those invoices that are asked of the same order if the last invoice is asked of that order then the pr_status of the order would be PR_LAST
                        // and if we pay any one of that order invoice, the order pr_status will be changed from PR_LAST to PR_COMPLETED
                        // so when paying the rest of the invoices of that same order we must set the variable $orderPrStatus value (to PR_COMPLETED), even if the order shows PR_COMPLETED
                        // this way we will have a variable to assign to the pr_status of order table as we did below (i.e = 'pr_status' => $orderPrStatus,)
                        $orderPrStatus = Order::ORDER_PR_COMPLETED;
                    }


                    $success = $invoice->update([
                        'status' => Invoice::INVOICE_STATUS_PAID,
                        'paid_date' => $today,
                    ]);
                    //
                    if (!$success) {
                        return response()->json(['message' => 'Invoice Update Failed'], 500);
                    }



                    $successTwo = $invoice->order()->update([
                        'pr_status' => $orderPrStatus,
                    ]);
                    //
                    if (!$successTwo) {
                        return response()->json(['message' => 'Order Update Failed'], 500);
                    }


                    // $invoiceIdList[] = Invoice::find($invoice->id); // consumes more resource

                    $invoiceIdList[] = $invoice->id; // USED
                

                }




                // this get all the invoices updated above
                $invoicesData = Invoice::whereIn('id', $invoiceIdList)->with('order')->latest()->get();   
                return InvoiceForOrganizationResource::collection($invoicesData);
                */

            }


        });

        return $var;
    }



    /**
     * NOT FUNCTIONAL CURRENTLY. 
     * 
     * this function is made UN-functional currently
     * 
     * the functionality under it is moved to another class
     * 
     * 
     * 
     * telebirr call back , to confirm payment // for organization
     */
    // public function payInvoicesCallbackTelebirr(PayInvoicesCallbackTelebirrRequest $request)
    // {
    //     //
    //     DB::transaction(function () use ($request) {

    //         // if paid status code from the bank is NOT 200 -> i will log and abort // abrham samson check
    //         // if paid status code from the bank is 200,  ->  I wil do the following // abrham samson check



    //         // todays date
    //         $today = now()->format('Y-m-d');



    //         /* $invoiceIdList = []; */


    //         // Get the invoice_code from the request
    //         $invoiceCode = $request['invoice_code'];


    //         // Fetch all invoices where invoice_code matches the one from the request
    //         $invoices = Invoice::where('invoice_code', $invoiceCode)->get(); // this should NOT be exists().  this should be get(), because i am going to use actual data (records) of $invoices in the below foreach
    //         //
    //         if (!$invoices) {
    //             // I must CHECK this condition 
    //             Log::alert('BOA: the invoice_code does not exist!');
    //             abort(422, 'the invoice_code does not exist!');
    //         }



    //         // Update all invoices with the sent invoice_code
    //         $success = Invoice::where('invoice_code', $invoiceCode)->update([
    //             'status' => Invoice::INVOICE_STATUS_PAID,
    //             'paid_date' => $today,
    //         ]);
    //         // Handle invoice update failure
    //         if (!$success) {
    //             return response()->json(['message' => 'Invoice Update Failed'], 500);
    //         }


    //         // in the following foreach i am going to update the PARENT ORDER of each INVOICE one by one
    //         foreach ($invoices as $invoice) {
    //             if ($invoice->order->pr_status === Order::ORDER_PR_STARTED) {
    //                 $orderPrStatus = Order::ORDER_PR_STARTED;
    //             } else if ($invoice->order->pr_status === Order::ORDER_PR_LAST) {
                    
    //                 $orderPrStatus = Order::ORDER_PR_COMPLETED;

    //                 // this is no longer used since i am controlling it in invoice asking,
    //                 // which means in invoice asking i will prevent super_admin not ask another invoice for an order if there is an already UnPaid invoice for that order in invoices table
    //                 // $orderInvoicesPaymentCheck = Invoice::where('order_id', $invoice->order->id)
    //                 //                 ->where('status', Invoice::INVOICE_STATUS_NOT_PAID)
    //                 //                 ->get();

    //                 // if ($orderInvoicesPaymentCheck->isEmpty()) {
    //                 //     $orderPrStatus = Order::ORDER_PR_COMPLETED;
    //                 // } else {
    //                 //     $orderPrStatus = Order::ORDER_PR_LAST;
    //                 // }
                    
    //             } else if ($invoice->order->pr_status === Order::ORDER_PR_COMPLETED) { 
    //                 // CURRENTLY THIS WILL NOT HAPPEN BECAUSE , I AM HANDLING IT WHEN 'SUPER_ADMIN' ASKS PR
    //                     //
    //                     // i added this condition because (IN CASE I DID NOT HANDLE THIS CASE when PR IS ASKED BY 'SUPER_ADMIN' - the following may happen) 
    //                             //
    //                             // a multiple pr request can be made to the same order in consecutive timelines one after the other 
    //                             // and from those invoices that are asked of the same order if the last invoice is asked of that order then the pr_status of the order would be PR_LAST
    //                             // and if we pay any one of that order invoice, the order pr_status will be changed from PR_LAST to PR_COMPLETED
    //                             // so when paying the rest of the invoices of that same order we must set the variable $orderPrStatus value (to PR_COMPLETED), even if the order shows PR_COMPLETED
    //                             // this way we will have a variable to assign to the pr_status of order table as we did below (i.e = 'pr_status' => $orderPrStatus,)
    //                 $orderPrStatus = Order::ORDER_PR_COMPLETED;
    //             }

                

    //             // Update the order pr_status
    //             $successTwo = $invoice->order()->update([
    //                 'pr_status' => $orderPrStatus,
    //             ]);
    //             // Handle order update failure
    //             if (!$successTwo) {
    //                 return response()->json(['message' => 'Order Update Failed'], 500);
    //             }

    //             /* $invoiceIdList[] = $invoice->id; */
    //         }

    //         // since it is call back we will not return value to the banks
    //         // or may be 200 OK response // check abrham samson
    //         //
    //         // // Fetch the above updated invoices based on the invoice ids
    //         // $invoicesData = Invoice::whereIn('id', $invoiceIdList)->with('order')->latest()->get();

    //         // return InvoiceForOrganizationResource::collection($invoicesData);
            
    //     });

    //     // return $var;
    // }



    public function testboa() 
    {
        $boaOrganizationPaymentService = new BOAOrganizationPaymentService();
        $valuePayment = $boaOrganizationPaymentService->initiatePaymentForPR(48, "9387kh4ohf734dddd".(string)time());

        return $valuePayment; // to return any value , including a RENDERED VIEW value from BOAPrPaymentService

        /*
        // to call a ROUTE from web.php
        // return response()->json(['toPayUrl' => route('pay.with.boa', $valuePayment)]); // better suited for returning model object (i.e. $invoice object) 
        */
    }


    public function testTelebirrApplyFabricToken() 
    {
        $teleBirrOrganizationPaymentService = new TeleBirrOrganizationPaymentService();
        $valuePayment = $teleBirrOrganizationPaymentService->applyFabricToken();

        return $valuePayment; 

    }


    public function testTelebirr() 
    {
        $teleBirrOrganizationPaymentService = new TeleBirrOrganizationPaymentService();
        $valuePayment = $teleBirrOrganizationPaymentService->createOrder("SampleTitle".(string)time(), "1"); // SampleTitle is the InvoiceCode

        return $valuePayment; 

    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInvoiceRequest $request)
    {
        //
        // $var = DB::transaction(function () use ($request) {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceRequest $request, string $id)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
