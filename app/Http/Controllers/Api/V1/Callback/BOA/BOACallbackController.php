<?php

namespace App\Http\Controllers\Api\V1\Callback\BOA;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CallbackRequests\BOA\BOACallbackRequest;
use App\Services\Api\V1\Callback\Customer\BOA\BOACustomerCallbackService;
use App\Services\Api\V1\Callback\OrganizationUser\BOA\BOAOrganizationCallbackService;

class BOACallbackController extends Controller
{
    /**
     * These are Constants for payments 
     * They will be APPENDED on ID of invoices as PREFIX, before the those invoice IDs are sent to the banks
     * //
     * // DURING CALL Backs From BANKs,
     * //       - they send us back those PREFIXed invoice IDs
     * //       - we use those PREFIXEs to identify which user type owens that invoice ID
     * //       - so FIRST we go to that "user type invoice table" THEN we do confirmation on the payment of that invoice id
     * 
     * 
     * 
     * from customer or organization to adiamat
     *      //
     *      "OPR-" = (organization PR) payment
     *      //
     *      "ICI-" = (individual customer initial) payment
     *      "ICF-" = (individual customer final) payment
     * 
     * from adiamat to others payment
     *      // NOTE : - 
     *      //
     *      "VOO-" = (vehicle of Order) payment
     *      //
     *      "DTF-" = (Driver Trip Fuel) payment
     * 
     * 
     * 
     */
    public function payInvoicesCallback(BOACallbackRequest $request)
    {
        //
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
