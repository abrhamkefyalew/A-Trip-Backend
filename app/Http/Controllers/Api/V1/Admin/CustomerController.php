<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
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
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::whereNotNull('id');

        // use Filtering service OR Scope to do this
        if ($request->has('phone_number_search')) {
            if (isset($request['phone_number_search'])) {
                $phoneNumber = $request['phone_number_search'];

                $customers = $customers->where('phone_number', $phoneNumber);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }
        }

        $customerData = $customers->with('media', 'address')->latest()->paginate(FilteringService::getPaginate($request));

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
        $this->authorize('view', $customer);

        return CustomerResource::make($customer->load('media', 'address'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        //
        $var = DB::transaction(function () use ($request, $customer) {

            
            
            $success = $customer->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 500);
            }
            

            if ($request->has('country') || $request->has('city')) {
                if ($customer->address) {
                    $customer->address()->update([
                        'country' => $request->input('country'),
                        'city' => $request->input('city'),
                    ]);
                } else {
                    $customer->address()->create([
                        'country' => $request->input('country'),
                        'city' => $request->input('city'),
                    ]);
                }
            }


            if ($request->has('customer_profile_image')) {
                $file = $request->file('customer_profile_image');
                $clearMedia = $request->input('customer_profile_image_remove', false);
                $collectionName = Customer::CUSTOMER_PROFILE_PICTURE;
                MediaService::storeImage($customer, $file, $clearMedia, $collectionName);
            }


            $updatedCustomer = Customer::find($customer->id);
            
            return CustomerResource::make($updatedCustomer->load('media', 'address'));
            
        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        //
    }
}
