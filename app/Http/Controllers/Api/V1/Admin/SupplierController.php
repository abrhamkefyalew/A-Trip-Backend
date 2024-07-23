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
                'is_approved' => (int) $request->input('is_approved', 0), // this works also
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


            if ($request->has('supplier_id_image')) {
                $file = $request->file('supplier_id_image');
                $clearMedia = false; // or true // // NO supplier image remove, since it is the first time the supplier is being stored
                $collectionName = Supplier::SUPPLIER_ID_PICTURE;
                MediaService::storeImage($supplier, $file, $clearMedia, $collectionName);
            }

            if ($request->has('supplier_passport_image')) {
                $file = $request->file('supplier_passport_image');
                $clearMedia = false; // or true // // NO supplier image remove, since it is the first time the supplier is being stored 
                $collectionName = Supplier::SUPPLIER_PASSPORT_PICTURE;
                MediaService::storeImage($supplier, $file, $clearMedia, $collectionName);
            }

            if ($request->has('supplier_profile_image')) {
                $file = $request->file('supplier_profile_image');
                $clearMedia = false; // or true // // NO supplier image remove, since it is the first time the supplier is being stored
                $collectionName = Supplier::SUPPLIER_PROFILE_PICTURE;
                MediaService::storeImage($supplier, $file, $clearMedia, $collectionName);
            }

            
            return SupplierResource::make($supplier->load('vehicles', 'media', 'address'));

        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        //
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
