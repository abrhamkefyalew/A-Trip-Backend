<?php

namespace App\Http\Controllers\Api\V1\Callback\BOA;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CallbackRequests\BOA\BOACallbackRequest;
use App\Services\Api\V1\Callback\Customer\BOA\BOACustomerCallbackService;
use App\Services\Api\V1\Callback\OrganizationUser\BOA\BOAOrganizationCallbackService;

class BOACallbackController extends Controller
{
    /**
     * payment callback for invoice (comes from banks)
     */
    public function payInvoicesCallback(BOACallbackRequest $request)
    {
        //
        //
        Log::info("BOA callback info: " . json_encode(['Callback request Value' => $request->all()]));
        Log::info("BOA callback info: " . json_encode(['Callback request Headers: ' => $request->header()]));


        // TODO 
        //              // abrham samson check
        //  the SIGNATURE FROM BOA should be Checked,
        //      I.E. i should sign the request with my private key (just as i did during payment)  - & -  compare it with the Signature that BOA sent
        //              => and only if the both Signatures are EQUAL that i shall continue to the following operations


        // BEFORE PROCEEDING to the next step we need to check IF the Payment was ACTUALLY SUCCESSFUL
        // use








        // NOT REALLY Check abrham samson
        // we need to store the callback body : - $request->all()   in the appropriate INVOICE table (i.e. based on the prefix)



        //
        if (substr($request['invoice_reference'], 0, 4) == config('constants.payment.customer_to_business.organization_pr')) {
            
            $boaOrganizationCallbackService = new BOAOrganizationCallbackService();
            $handlePaymentByBoaForPRCallbackValue = $boaOrganizationCallbackService->handlePaymentForPRCallback($request['invoice_reference']);


            // since it is call back we will not return value to the banks
            // or may be 200 OK response // check abrham samson

        }
        
        else if (substr($request['invoice_reference'], 0, 4) == config('constants.payment.customer_to_business.individual_customer_initial')) {
            
            // pass the whole invoice reference for the callback
            $boaCustomerPaymentService = new BOACustomerCallbackService($request['invoice_reference']);

            // Calling a callback non static method
            $value = $boaCustomerPaymentService->handleInitialPaymentForVehicleCallback();

            // since it is call back we will not return value to the banks
            // or may be 200 OK response // check abrham samson

        }
        else if (substr($request['invoice_reference'], 0, 4) == config('constants.payment.customer_to_business.individual_customer_final')) {
            
            // pass the whole invoice reference for the callback
            $boaCustomerPaymentService = new BOACustomerCallbackService($request['invoice_reference']);

            // Calling a callback non static method
            $value = $boaCustomerPaymentService->handleFinalPaymentForVehicleCallback();

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
