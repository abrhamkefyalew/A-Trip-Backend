<?php

namespace App\Http\Controllers\Api\V1\Supplier;

use App\Models\Vehicle;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $vehicles = Vehicle::where('supplier_id', $supplier->id);

        if ($request->has('driver_id_search')) {
            if (isset($request['driver_id_search'])) {
                $driverId = $request['driver_id_search'];

                $vehicles = $vehicles->where('driver_id', $driverId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }
        }
        if ($request->has('vehicle_name_id_search')) {
            if (isset($request['vehicle_name_id_search'])) {
                $vehicleNameId = $request['vehicle_name_id_search'];

                $vehicles = $vehicles->where('vehicle_name_id', $vehicleNameId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }
        }
        if ($request->has('with_driver_search')) {
            if (isset($request['with_driver_search'])) {
                $withDriverBoolValue = $request['with_driver_search'];  // IF supplier is filtering vehicles with vehicle_name_id TO ACCEPT AN ORDER,  then the with_driver_search value should be 0 // since supplier should ONLY see vehicles that have no driver

                $vehicles = $vehicles->where('with_driver', $withDriverBoolValue);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }
        }
        


        $vehiclesData = $vehicles->with('media', 'vehicleName', 'address', 'driver', 'bank')->latest()->paginate(FilteringService::getPaginate($request));       // this get multiple vehicles of the logged in supplier

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

        return VehicleResource::make($vehicle->load('media', 'vehicleName', 'address', 'driver', 'bank'));
    }

    /**
     * Update the specified resource in storage.
     * 
     * 
     * supplier can change vehicle is_available here = is_available can be switched between (VEHICLE_NOT_AVAILABLE, VEHICLE_AVAILABLE, VEHICLE_ON_TRIP)
     * 
     * supplier can do driver DETACH and ATTACH here
     * 
     * supplier can also update other vehicle attributes (except his own supplier_id)
     * 
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        //
        $var = DB::transaction(function () use ($request, $vehicle) {

            $user = auth()->user();
            $supplier = Supplier::find($user->id);

            
            if ($supplier->id != $vehicle->supplier_id) {
                // this vehicle is NOT be owned by the logged in supplier
                return response()->json(['message' => 'invalid Vehicle is selected or Requested. or the requested Vehicle is not found. Deceptive request Aborted.'], 401);
            }
            

            $success = $vehicle->update($request->validated());

            if ($request->has('country') || $request->has('city')) {
                if ($vehicle->address) {
                    $vehicle->address()->update([
                        'country' => $request->input('country'),
                        'city' => $request->input('city'),
                    ]);
                } else {
                    $vehicle->address()->create([
                        'country' => $request->input('country'),
                        'city' => $request->input('city'),
                    ]);
                }
            }



            // MEDIA CODE SECTION
            // do not forget to do the MEDIA UPDATE also // check abrham samson // remember



            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 422);
            }

            $updatedVehicle = Vehicle::find($vehicle->id);


            return VehicleResource::make($updatedVehicle->load('media', 'vehicleName', 'address', 'driver', 'bank'));

            
        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        //
    }
}
