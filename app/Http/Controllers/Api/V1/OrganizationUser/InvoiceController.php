<?php

namespace App\Http\Controllers\Api\V1\OrganizationUser;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\OrganizationUser;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
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
                'sometimes', 'string', Rule::in([Invoice::INVOICE_STATUS_NOT_PAYED, Invoice::INVOICE_STATUS_PAYED]),
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
     * But Filtered by invoice_code and OrganizationId
     * 
     * it is used when the organization INTENDS TO PAY invoices with invoice code
     * 
     * THIS ONE IS USED WHEN ORGANIZATION WANTS TO SEE ALL INVOICES BASED ON INVOICE - INTENDING TO PAY
     * 
     * CAN ONLY see the UNPAID invoices of some invoice code (NOT PAID invoices of an invoice_code)
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

                $invoices = $invoices->where('invoice_code', $invoiceCode);

                $invoiceData = $invoices->with('order')->latest()->get();

                // $totalPriceAmount now contains the total price_amount of all invoices with the specified 'invoice_code' , status unpaid and paid_date null // it will do add all invoices with the specified invoice_code (that are not paid and have null paid date)
                $totalPriceAmount = Invoice::where('invoice_code', $invoiceCode)
                    ->where('status', Invoice::INVOICE_STATUS_NOT_PAYED)
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
    public function store(StoreInvoiceRequest $request)
    {
        //
        // $var = DB::transaction(function () {
            
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
