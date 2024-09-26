<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Customer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminRequests\StoreCustomerRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateCustomerRequest;


class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $this->authorize('viewAny', Customer::class);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        // $this->authorize('view', $customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        //
    }
}
