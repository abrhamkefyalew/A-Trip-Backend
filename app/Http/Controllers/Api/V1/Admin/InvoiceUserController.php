<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\InvoiceUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminRequests\StoreInvoiceUserRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateInvoiceUserRequest;


class InvoiceUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', InvoiceUser::class);
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
        // $this->authorize('view', $invoiceUser);
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
