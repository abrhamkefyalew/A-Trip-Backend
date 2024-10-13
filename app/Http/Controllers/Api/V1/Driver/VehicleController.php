<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\VehicleResources\VehicleResource;
use App\Http\Requests\Api\V1\DriverRequests\UpdateVehicleRequest;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //

        $user = auth()->user();
        $driver = Driver::find($user->id);

        $vehicles = Vehicle::where('driver_id', $driver->id);


        // this filter is NOT necessary since Driver can only see one vehicle
        if ($request->has('vehicle_name_id_search')) {
            if (isset($request['vehicle_name_id_search'])) {
                $vehicleNameId = $request['vehicle_name_id_search'];

                $vehicles = $vehicles->where('vehicle_name_id', $vehicleNameId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 422);
            }
        }

        
        $vehiclesData = $vehicles->with('media', 'vehicleName', 'address', 'supplier', 'bank')->latest()->paginate(FilteringService::getPaginate($request));       // this get the single vehicle of the logged in driver // only one vehicle should be returned

        return VehicleResource::collection($vehiclesData);
    }

    /**
     * Store a newly created resource in storage.
     * 
     * Driver should NOT store vehicle
     * 
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
    public function show(Vehicle $vehicle)
    {
        //
        // get the logged in driver
        $user = auth()->user();
        $driver = Driver::find($user->id);

        
        if ($driver->id != $vehicle->driver_id) {
            // this vehicle is NOT be owned by the logged in driver
            return response()->json(['message' => 'invalid Vehicle is selected or Requested. or the requested Vehicle is not found. Deceptive request Aborted.'], 401);
        }

        return VehicleResource::make($vehicle->load('media', 'vehicleName', 'address', 'supplier', 'bank', 'bids'));
    }

    /**
     * Update the specified resource in storage.
     * 
     * 
     * driver can change vehicle is_available here = is_available can be switched between (VEHICLE_NOT_AVAILABLE, VEHICLE_AVAILABLE, VEHICLE_ON_TRIP)
     * 
     * driver can also update some of the other vehicle attributes // but NOT all of them // 
     * Because of restrictions the Driver can only update some of the vehicle attributes , not all of them
     * 
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        //
        $var = DB::transaction(function () use ($request, $vehicle) {

            $user = auth()->user();
            $driver = Driver::find($user->id);

            
            if ($driver->id != $vehicle->driver_id) {
                // this vehicle is NOT be owned by the logged in driver
                return response()->json(['message' => 'invalid Vehicle is selected or Requested. or the requested Vehicle is not found. Deceptive request Aborted.'], 401);
            }
            

            $success = $vehicle->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 422);
            }
            

            // since the driver is holding the vehicle moving it around in different locations, he too can update the location of the vehicle
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
            // Driver should NOT be allowed to update any of the media section


            $updatedVehicle = Vehicle::find($vehicle->id);


            return VehicleResource::make($updatedVehicle->load('media', 'vehicleName', 'address', 'supplier', 'bank', 'bids'));

            
        });

        return $var;
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
