<?php

namespace App\Http\Controllers\Api\V1\Callback\TeleBirr;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CallbackRequests\TeleBirr\TeleBirrCallbackRequest;
use App\Services\Api\V1\Callback\Customer\TeleBirr\TeleBirrCustomerCallbackService;
use App\Services\Api\V1\Callback\OrganizationUser\TeleBirr\TeleBirrOrganizationCallbackService;

class TeleBirrCallbackController extends Controller
{
    /**
     * payment callback for invoice (comes from banks)
     */
    public function payInvoicesCallback(TeleBirrCallbackRequest $request)
    {
        //
        // Log::info("Callback info Telebirr : ". response()->json(['request value' => $request]));
        Log::info("Callback info Telebirr: " . json_encode(['request value' => $request->all()]));
        
        //
        if (substr($request['invoice_reference'], 0, 4) == config('constants.payment.customer_to_business.organization_pr')) {
            
            $teleBirrOrganizationCallbackService = new TeleBirrOrganizationCallbackService();
            $handlePaymentByTeleBirrForPRCallbackValue = $teleBirrOrganizationCallbackService->handlePaymentForPRCallback($request['invoice_reference']);


            // since it is call back we will not return value to the banks
            // or may be 200 OK response // check abrham samson

        }
        
        else if (substr($request['invoice_reference'], 0, 4) == config('constants.payment.customer_to_business.individual_customer_initial')) {
            
            // pass the whole invoice reference for the callback
            $teleBirrCustomerPaymentService = new TeleBirrCustomerCallbackService($request['invoice_reference']);

            // Calling a callback non static method
            $value = $teleBirrCustomerPaymentService->handleInitialPaymentForVehicleCallback();

            // since it is call back we will not return value to the banks
            // or may be 200 OK response // check abrham samson

        }
        else if (substr($request['invoice_reference'], 0, 4) == config('constants.payment.customer_to_business.individual_customer_final')) {
            
            // pass the whole invoice reference for the callback
            $teleBirrCustomerPaymentService = new TeleBirrCustomerCallbackService($request['invoice_reference']);

            // Calling a callback non static method
            $value = $teleBirrCustomerPaymentService->handleFinalPaymentForVehicleCallback();

            // since it is call back we will not return value to the banks
            // or may be 200 OK response // check abrham samson

        }
        

    }





    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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
    public function update(Request $request, string $id)
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
