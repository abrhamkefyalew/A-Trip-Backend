<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\InvoiceUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InvoiceUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
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
    public function show(InvoiceUser $invoiceUser)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InvoiceUser $invoiceUser)
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
