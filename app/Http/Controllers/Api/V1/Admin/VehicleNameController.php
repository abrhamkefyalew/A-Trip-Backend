<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\VehicleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminRequests\StoreVehicleNameRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateVehicleNameRequest;

class VehicleNameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVehicleNameRequest $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
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
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VehicleName $vehicleName)
    {
        //
    }
}
