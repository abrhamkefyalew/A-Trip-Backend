<?php

namespace App\Http\Controllers\Api\V1\OrganizationUser;

use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\OrganizationUser;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Services\Api\V1\OrganizationUser\PrPaymentService;
use App\Http\Requests\Api\V1\OrganizationUserRequests\PayInvoiceRequest;
use App\Http\Requests\Api\V1\OrganizationUserRequests\StoreInvoiceRequest;
use App\Http\Requests\Api\V1\OrganizationUserRequests\UpdateInvoiceRequest;
use App\Http\Resources\Api\V1\InvoiceResources\InvoiceForOrganizationResource;

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
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }
        if ($request->has('invoice_code_search')) {
            if (isset($request['invoice_code_search'])) {
                $invoiceCode = $request['invoice_code_search'];

                $invoices = $invoices->where('invoice_code', $invoiceCode);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            } 
        }
        if ($request->has('invoice_status_search')) {
            if (isset($request['invoice_status_search'])) {
                $invoiceStatus = $request['invoice_status_search'];

                $invoices = $invoices->where('status', $invoiceStatus);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
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
        // $this->authorize('viewAny', Invoice::class);

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
                return response()->json(['message' => 'Required parameter "invoice_code_search" is empty or Value Not Set'], 422);
            } 
        }
        else {
            return response()->json(['message' => 'Required parameter "invoice_code_search" is missing'], 422);
        } 
        
    }



    /**
     * Store a newly created resource in storage.
     */
    public function payInvoices(PayInvoiceRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {



            if ($request->has('invoices')) {

                // Check if all invoices have the same organization_id            // a PR payment request or multiple PR payment request should be sent for only one organization at a time
                $invoiceIds = $request->input('invoices.*.invoice_id');
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
                    return response()->json(['message' => 'invalid Order is selected or Requested. or the requested Order is not found. Deceptive request Aborted.'], 401);
                }


                //  check if there is duplicate invoice_id in the JSON and if there Duplicate invoice_id is return ERROR
                $invoiceIds = collect($request->invoices)->pluck('invoice_id');
                    //
                if ($invoiceIds->count() !== $invoiceIds->unique()->count()) {
                    return response()->json(['message' => 'Duplicate invoice_id values are not allowed.'], 400);
                }
                // Continue processing the request if no duplicate invoice_id values are found


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
                        return response()->json(['message' => 'PR have not been started for this order yet. order: ' . $invoice->order->id . ' , The order have pr_status NULL.'], 404);
                    }
                    if ($invoice->order->pr_status === Order::ORDER_PR_COMPLETED) {
                        return response()->json(['message' => 'all PR is paid for this order: ' . $invoice->order->id . ' , The order have PR_COMPLETED status.'], 404);
                    }
                    if ($invoice->order->pr_status === Order::ORDER_PR_TERMINATED) {
                        return response()->json(['message' => 'this order PR is terminated for some reason. please check with the organization and Super Admin why PR is terminated. order: ' . $invoice->order->id . ' , The order have PR_TERMINATED status.'], 404);
                    }

                    // check if the actual invoice is Paid // if the this invoice have status = PAID
                    if ($invoice->status === Invoice::INVOICE_STATUS_PAID) {
                        return response()->json(['message' => 'This Invoice is Already Paid.  Invoice: ' . $$invoice->id . ' , The Invoice have PAID status.'], 404);
                    }



                }


                // check abrham samson
                // do something here to handle = if the actual associated ORDER of EACH requested INVOICE does NOT exit     
                // should be checked separately using foreach or another method to handle those separately



                // compare actual total price from database with the sent total price from frontend
                $totalPriceAmount = Invoice::whereIn('id', $invoiceIds)
                    ->sum('price_amount');

                if ($totalPriceAmount !== $requestData['price_amount_total']) {
                    return response()->json(['message' => 'the total price sent in the request does NOT match the total price of the requested invoice IDs in the database.'], 404);
                }

                // do the actual payment
                $valuePayment = PrPaymentService::payPrs($totalPriceAmount);

                if ($valuePayment != true) {
                    return response()->json(['message' => 'payment operation failed from the banks side'], 500);
                }












                $invoiceIds = [];

                // Now We are sure all the impurities are filtered in the above foreach
                // So do the ACTUAL Operations on each of the invoices sent in the request
                foreach ($request->safe()->invoices as $requestData) {

                    $invoice = Invoice::find($requestData['invoice_id']);

                    if ($invoice->order->pr_status === Order::ORDER_PR_STARTED) {
                        $orderPrStatus = Order::ORDER_PR_STARTED;
                    }
                    if ($invoice->order->pr_status === Order::ORDER_PR_LAST) {
                        $orderPrStatus = Order::ORDER_PR_COMPLETED;
                    }


                    $success = $invoice->update([
                        'status' => Invoice::INVOICE_STATUS_PAID,
                        'paid_date' => $today,
                    ]);
                    //
                    if (!$success) {
                        return response()->json(['message' => 'Invoice Update Failed'], 422);
                    }



                    $successTwo = $invoice->order()->update([
                        'pr_status' => $orderPrStatus,
                    ]);
                    //
                    if (!$successTwo) {
                        return response()->json(['message' => 'Order Update Failed'], 422);
                    }


                    // $invoiceIds[] = Invoice::find($invoice->id); // consumes more resource

                    $invoiceIds[] = $invoice->id; // USED
                

                }




                // this get the invoices created from the above two if conditions 
                $invoicesData = Invoice::whereIn('id', $invoiceIds)->with('order')->latest()->paginate(FilteringService::getPaginate($request));   
                return InvoiceForOrganizationResource::collection($invoicesData);

            }


        });

        return $var;
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
