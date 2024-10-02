<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\AdminRequests\StoreCustomerRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResources\CustomerResource;


class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Customer::class);

        $customers = Customer::whereNotNull('id');

        // use Filtering service OR Scope to do this
        if ($request->has('phone_number_search')) {
            if (isset($request['phone_number_search'])) {
                $phoneNumber = $request['phone_number_search'];

                $customers = $customers->where('phone_number', $phoneNumber);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }
        }

        $customerData = $customers->with('media')->latest()->paginate(FilteringService::getPaginate($request));

        return CustomerResource::collection($customerData);
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
