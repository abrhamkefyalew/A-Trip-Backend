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
        // Log::info("Callback info Telebirr : ". response()->json(['request value' => $request])); // NOT working
        Log::info("Callback info Telebirr: " . json_encode(['Callback request Value' => $request->all()]));
        Log::info("Callback info Telebirr: " . json_encode(['Callback request Headers: ' => $request->header()]));

        // BEFORE PROCEEDING
        // use 'trade_status' to check if payment is successful 
        // 402 status code is = payment required
        // 
        if ($request['trade_status'] != "Completed") {
            Log::alert('trade_status is NOT-Completed - so Payment NOT success - (payment required) for merch_order_id : - ' . $request['merch_order_id']);
            abort(402, 'trade_status is NOT-Completed - so Payment NOT success - (payment required) for merch_order_id : - ' . $request['merch_order_id']);
        }
        
        //
        if (substr($request['merch_order_id'], 0, 4) == config('constants.payment.customer_to_business.organization_pr')) {
            
            $teleBirrOrganizationCallbackService = new TeleBirrOrganizationCallbackService();
            $handlePaymentByTeleBirrForPRCallbackValue = $teleBirrOrganizationCallbackService->handlePaymentForPRCallback($request['merch_order_id']);


            // since it is call back we will not return value to the banks
            // or may be 200 OK response // check abrham samson

        }
        
        else if (substr($request['merch_order_id'], 0, 4) == config('constants.payment.customer_to_business.individual_customer_initial')) {
            
            // pass the whole invoice reference for the callback
            $teleBirrCustomerPaymentService = new TeleBirrCustomerCallbackService($request['merch_order_id']);

            // Calling a callback non static method
            $value = $teleBirrCustomerPaymentService->handleInitialPaymentForVehicleCallback();

            // since it is call back we will not return value to the banks
            // or may be 200 OK response // check abrham samson

        }
        else if (substr($request['merch_order_id'], 0, 4) == config('constants.payment.customer_to_business.individual_customer_final')) {
            
            // pass the whole invoice reference for the callback
            $teleBirrCustomerPaymentService = new TeleBirrCustomerCallbackService($request['merch_order_id']);

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
