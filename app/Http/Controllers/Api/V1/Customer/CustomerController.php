<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
use App\Http\Requests\Api\V1\CustomerRequests\UpdateCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResources\CustomerForCustomerResource;

class CustomerController extends Controller
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
    public function show(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        //
        $var = DB::transaction(function () use ($request, $customer) {

            $user = auth()->user();
            $customerLoggedIn = Customer::find($user->id);

            
            if ($customerLoggedIn->id != $customer->id) {
                
                return response()->json(['message' => 'invalid Customer is selected or Requested. Deceptive request Aborted.'], 401);
            }
            
            $success = $customer->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 422);
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
            
            return CustomerForCustomerResource::make($updatedCustomer->load('media', 'address'));
            
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
