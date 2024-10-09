<?php

namespace App\Http\Controllers\Api\V1\Supplier;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
use App\Http\Resources\Api\V1\SupplierResources\SupplierResource;
use App\Http\Requests\Api\V1\SupplierRequests\UpdateSupplierRequest;

class SupplierController extends Controller
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
        $var = DB::transaction(function () use ($request, $supplier) {
            
            $user = auth()->user();
            $supplierLoggedIn = Supplier::find($user->id);

            
            if ($supplierLoggedIn->id != $supplier->id) {
                
                return response()->json(['message' => 'invalid Supplier is selected or Requested. Deceptive request Aborted.'], 401);
            }


            $success = $supplier->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 422);
            }
            

            if ($request->has('country') || $request->has('city')) {
                if ($supplier->address) {
                    $supplier->address()->update([
                        'country' => $request->input('country'),
                        'city' => $request->input('city'),
                    ]);
                } else {
                    $supplier->address()->create([
                        'country' => $request->input('country'),
                        'city' => $request->input('city'),
                    ]);
                }
            }



            // MEDIA CODE SECTION
            // REMEMBER = (clearMedia) ALL media should NOT be Cleared at once, media should be cleared by id, like one picture. so the whole collection should NOT be cleared using $clearMedia the whole collection // check abrham samson // remember
            //
            if ($request->has('supplier_id_front_image')) {
                $file = $request->file('supplier_id_front_image');
                $clearMedia = $request->input('supplier_id_front_image_remove', false);
                $collectionName = Supplier::SUPPLIER_ID_FRONT_PICTURE;
                MediaService::storeImage($supplier, $file, $clearMedia, $collectionName);
            }

            if ($request->has('supplier_id_back_image')) {
                $file = $request->file('supplier_id_back_image');
                $clearMedia = $request->input('supplier_id_back_image_remove', false);
                $collectionName = Supplier::SUPPLIER_ID_BACK_PICTURE;
                MediaService::storeImage($supplier, $file, $clearMedia, $collectionName);
            }

            if ($request->has('supplier_profile_image')) {
                $file = $request->file('supplier_profile_image');
                $clearMedia = (isset($request['supplier_profile_image_remove']) ? $request['supplier_profile_image_remove'] : false);
                $collectionName = Supplier::SUPPLIER_PROFILE_PICTURE;
                MediaService::storeImage($supplier, $file, $clearMedia, $collectionName);
            }

            
            $updatedSupplier = Supplier::find($supplier->id);

            return SupplierResource::make($updatedSupplier->load('media', 'address', 'vehicles'));

        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        //
    }
}
