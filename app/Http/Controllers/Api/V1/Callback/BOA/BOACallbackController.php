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
     * payment callback for invoice (comes from banks)
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
        if (substr($request['invoice_reference'], 0, 4) === 'OPR-') {
            
            $boaOrganizationCallbackService = new BOAOrganizationCallbackService();
            $handlePaymentByBoaForPRCallbackValue = $boaOrganizationCallbackService->handlePaymentForPRCallback($request['invoice_reference']);


            // since it is call back we will not return value to the banks
            // or may be 200 OK response // check abrham samson

        }
        
        else if (substr($request['invoice_reference'], 0, 4) === 'ICI-') {
            
            // pass the whole invoice reference for the callback
            $boaCustomerPaymentService = new BOACustomerCallbackService($request['invoice_reference']);

            // Calling a callback non static method
            $value = $boaCustomerPaymentService->handleInitialPaymentForVehicleCallback();

            // since it is call back we will not return value to the banks
            // or may be 200 OK response // check abrham samson

        }
        else if (substr($request['invoice_reference'], 0, 4) === 'ICF-') {
            
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
