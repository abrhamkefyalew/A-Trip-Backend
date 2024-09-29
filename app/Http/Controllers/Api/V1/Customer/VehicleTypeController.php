<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\VehicleType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\VehicleTypeResources\VehicleTypeResource;

class VehicleTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        if (isset($request['paginate'])) {
            if ($request['paginate'] == "all"){
                $vehicleType = VehicleType::get();
            }
            else {
                $vehicleType = VehicleType::paginate(FilteringService::getPaginate($request));
            }
        } else {
            $vehicleType = VehicleType::get();
        }


        return VehicleTypeResource::collection($vehicleType);
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
    public function show(VehicleType $vehicleType)
    {
        //
        return VehicleTypeResource::make($vehicleType->load('vehicleNames'));
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
