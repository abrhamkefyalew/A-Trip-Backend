<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\VehicleName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\AdminRequests\StoreVehicleNameRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateVehicleNameRequest;
use App\Http\Resources\Api\V1\VehicleNameResources\VehicleNameResource;

class VehicleNameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // do auth here

        // scope should be used here
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVehicleNameRequest $request)
    {
        //
        $var = DB::transaction(function () use($request) {
            $vehicleName = VehicleName::create($request->validated());

            // for the admin if the admin wants we can return only the equipment    or the hospitals that have this equipment 
            return VehicleNameResource::make($vehicleName);
        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(VehicleName $vehicleName)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVehicleNameRequest $request, VehicleName $vehicleName)
    {
        //
        $var = DB::transaction(function () use($request, $vehicleName) {
            $vehicleName->update($request->validated());

            return VehicleNameResource::make($vehicleName);
        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VehicleName $vehicleName)
    {
        //
    }
}
