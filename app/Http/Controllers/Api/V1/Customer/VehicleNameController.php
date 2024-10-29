<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\VehicleName;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\VehicleNameResources\VehicleNameResource;

class VehicleNameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        // use Filtering service OR Scope to do this
        if (isset($request['paginate'])) {
            if ($request['paginate'] == "all"){
                $vehicleName = VehicleName::with('vehicleType')->get();
            }
            else {
                $vehicleName = VehicleName::with('vehicleType')->paginate(FilteringService::getPaginate($request));
            }
        } 
        else {
            $vehicleName = VehicleName::with('vehicleType')->get();
        }


        return VehicleNameResource::collection($vehicleName);
    }


    public function searchByVehicleType(Request $request)
    {
        // $this->authorize('viewAny', VehicleName::class);

        // use Filtering service OR Scope to do this
        if ($request->has('vehicle_type_id_search')) {
            if (isset($request['vehicle_type_id_search'])) {
                $VehicleTypeId = $request['vehicle_type_id_search'];

                $vehicleName = VehicleName::where('vehicle_type_id', $VehicleTypeId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }
            
        }
        else {
            $vehicleName = VehicleName::whereNotNull('id');
        }
        

        $vehicleNameData = $vehicleName->paginate(FilteringService::getPaginate($request));

        return VehicleNameResource::collection($vehicleNameData);
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
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
