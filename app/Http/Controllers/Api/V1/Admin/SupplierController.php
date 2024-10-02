<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\AdminRequests\StoreSupplierRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateSupplierRequest;
use App\Http\Resources\Api\V1\SupplierResources\SupplierResource;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::whereNotNull('id');

        // use Filtering service OR Scope to do this
        if ($request->has('phone_number_search')) {
            if (isset($request['phone_number_search'])) {
                $phoneNumber = $request['phone_number_search'];

                $suppliers = $suppliers->where('phone_number', $phoneNumber);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }
        }

        $supplierData = $suppliers->with('media')->latest()->paginate(FilteringService::getPaginate($request));

        return SupplierResource::collection($supplierData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {


            $supplier = Supplier::create([
                'first_name' => $request['first_name'],
                'last_name' => $request['last_name'],
                'email' => $request['email'],
                'phone_number' => $request['phone_number'],
                'is_active' => (int) (isset($request['is_active']) ? $request['is_active'] : 1), // this works
                'is_approved' => (int) $request->input('is_approved', 1), // this works also    // // this column can ONLY be Set by the SUPER_ADMIN, // if Supplier is registering himself , he can NOT send the is_approved field
                                                                                                   // so this //is_approved// code part will be removed when the Supplier makes the request
            ]);



            $hasLocationData = ($request->has('country') ||
                $request->has('city')
            );

            if ($hasLocationData) {
                $supplier->address()->create([
                    'country' => $request->input('country'),
                    'city' => $request->input('city'),
                ]);
            }


            // NO supplier image remove, since it is the first time the supplier is being stored
            // also use the MediaService class to remove image


            if ($request->has('supplier_id_front_image')) {
                $file = $request->file('supplier_id_front_image');
                $clearMedia = false; // or true // // NO supplier image remove, since it is the first time the supplier is being stored
                $collectionName = Supplier::SUPPLIER_ID_FRONT_PICTURE;
                MediaService::storeImage($supplier, $file, $clearMedia, $collectionName);
            }

            if ($request->has('supplier_id_back_image')) {
                $file = $request->file('supplier_id_back_image');
                $clearMedia = false; // or true // // NO supplier image remove, since it is the first time the supplier is being stored 
                $collectionName = Supplier::SUPPLIER_ID_BACK_PICTURE;
                MediaService::storeImage($supplier, $file, $clearMedia, $collectionName);
            }

            if ($request->has('supplier_profile_image')) {
                $file = $request->file('supplier_profile_image');
                $clearMedia = false; // or true // // NO supplier image remove, since it is the first time the supplier is being stored
                $collectionName = Supplier::SUPPLIER_PROFILE_PICTURE;
                MediaService::storeImage($supplier, $file, $clearMedia, $collectionName);
            }

            
            return SupplierResource::make($supplier->load('media', 'address', 'vehicles'));

        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        // $this->authorize('view', $supplier);
        return SupplierResource::make($supplier->load('media', 'address', 'vehicles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        //
    }
}
