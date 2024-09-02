<?php

namespace App\Http\Controllers\Api\V1\Supplier;

use App\Models\Vehicle;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\VehicleResources\VehicleResource;
use App\Http\Requests\Api\V1\SupplierRequests\StoreVehicleRequest;
use App\Http\Requests\Api\V1\SupplierRequests\UpdateVehicleRequest;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        
        $user = auth()->user();
        $supplier = Supplier::find($user->id);
        
        if ($request->has('vehicle_name_id_search')) {
            if (isset($request['vehicle_name_id_search'])) {
                $vehicleNameId = $request['vehicle_name_id_search'];

                $vehicles = Vehicle::where('supplier_id', $supplier->id)->where('vehicle_name_id', $vehicleNameId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }

        }
        else {
            $vehicles = Vehicle::where('supplier_id', $supplier->id);
        }


        $vehiclesData = $vehicles->with('media', 'vehicleName', 'address', 'supplier', 'driver', 'bank')->latest()->paginate(FilteringService::getPaginate($request));       // this get multiple vehicles of the supplier

        return VehicleResource::collection($vehiclesData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVehicleRequest $request)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle)
    {
        //
        // get the logged in supplier
        $user = auth()->user();
        $supplier = Supplier::find($user->id);

        
        if ($supplier->id != $vehicle->supplier_id) {
            // this vehicle is NOT be owned by the logged in supplier
            return response()->json(['message' => 'invalid Vehicle is selected or Requested. or the requested Vehicle is not found. Deceptive request Aborted.'], 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        //
    }
}
