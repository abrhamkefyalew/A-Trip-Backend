<?php

namespace App\Http\Controllers;

use App\Models\InvoiceUser;
use App\Http\Requests\StoreInvoiceUserRequest;
use App\Http\Requests\UpdateInvoiceUserRequest;

class InvoiceUserController extends Controller
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
    public function store(StoreInvoiceUserRequest $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(InvoiceUser $invoiceUser)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceUserRequest $request, InvoiceUser $invoiceUser)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InvoiceUser $invoiceUser)
    {
        //
    }
}
