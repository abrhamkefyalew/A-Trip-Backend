<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\InvoiceTrip;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminRequests\StoreInvoiceTripRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateInvoiceTripRequest;

class InvoiceTripController extends Controller
{
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
    public function store(StoreInvoiceTripRequest $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(InvoiceTrip $invoiceTrip)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceTripRequest $request, InvoiceTrip $invoiceTrip)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InvoiceTrip $invoiceTrip)
    {
        //
    }
}
