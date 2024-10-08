<?php

namespace App\Http\Controllers\Api\V1\Supplier;

use App\Models\Vehicle;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
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

        // use Filtering service OR Scope to do this
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
        if ($request->has('plate_number_search')) {
            if (isset($request['plate_number_search'])) {
                $plateNumber = $request['plate_number_search'];

                $vehicles = $vehicles->where('plate_number', $plateNumber);
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
        $var = DB::transaction(function () use ($request) {

            $user = auth()->user();
            $supplier = Supplier::find($user->id);


            // ask samson
            // check the sent with_driver in the request as the following and its consequences
            // if "with_driver" = 0 , driver_id must NOT come in the request, otherwise = I will return ERROR 
            // if "with_driver" = 1 , driver_id MUST also come in the request, otherwise = I will return ERROR
            // should we return error for such requests.
            if ($request['with_driver'] == 0) {
                if ($request->has('driver_id')) {
                    return response()->json(['message' => 'request can NOT contain a driver_id. You have set with_driver = 0, so driver_id should NOT be included your request.'], 400);
                }
                // if ($request['driver_id'] !== null) {
                //     return response()->json(['message' => 'you can NOT set a driver_id in the request. You have set with_driver = 0, so driver_id must be null in your request.'], 400);
                // }
            }
            if ($request['with_driver'] == 1) {
                if (!$request->has('driver_id')) {
                    return response()->json(['message' => 'request missing driver_id. You have set with_driver = 1, so you must Provide driver_id for your vehicle with your request.'], 400);
                }
                if ($request['driver_id'] === null) {
                    return response()->json(['message' => 'driver_id value is NOT set in the request. You have set with_driver = 1, so you must Provide driver_id for your vehicle with your request.'], 400);
                }
            }

            
            $vehicle = Vehicle::create([
                'vehicle_name_id' => $request['vehicle_name_id'],
                'supplier_id' => $supplier->id,
                'driver_id' => $request['driver_id'],
                'vehicle_name' => $request['vehicle_name'],
                'vehicle_description' => $request['vehicle_description'],
                'vehicle_model' => $request['vehicle_model'],
                'plate_number' => $request['plate_number'],
                'year' => $request['year'],
                'is_available' => $request->input('is_available', Vehicle::VEHICLE_AVAILABLE),
                'with_driver' => (int) $request->input('with_driver', 0), // if the supplier_id does NOT send this field (the "with_driver" field) we will insert = 0 by default 
                                                                                    // 0 = means this vehicle do NOT have driver, i rent only the vehicle and NO driver will be included

                'bank_id' => $request['bank_id'],
                'bank_account' => $request['bank_account'],
                
                                                                                                   
            ]);



            // if the vehicle have an actual location , where it is currently located
            if ($request->has('country') || $request->has('city')) {
                $vehicle->address()->create([
                    'country' => $request->input('country'),
                    'city' => $request->input('city'),
                ]);
            }


            // NO vehicle image remove, since it is the first time the vehicle is being stored
            // also use the MediaService class to remove image

            if ($request->has('vehicle_libre_image')) {
                $file = $request->file('vehicle_libre_image');
                $clearMedia = false; // or true // // NO vehicle image remove, since it is the first time the vehicle is being stored 
                $collectionName = Vehicle::VEHICLE_LIBRE_PICTURE;
                MediaService::storeImage($vehicle, $file, $clearMedia, $collectionName);
            }
            
            if ($request->has('vehicle_third_person_image')) {
                $file = $request->file('vehicle_third_person_image');
                $clearMedia = false; // or true // // NO vehicle image remove, since it is the first time the vehicle is being stored
                $collectionName = Vehicle::VEHICLE_THIRD_PERSON_PICTURE;
                MediaService::storeImage($vehicle, $file, $clearMedia, $collectionName);
            }

            if ($request->has('vehicle_power_of_attorney_image')) {
                $file = $request->file('vehicle_power_of_attorney_image');
                $clearMedia = false; // or true // // NO vehicle image remove, since it is the first time the vehicle is being stored 
                $collectionName = Vehicle::VEHICLE_POWER_OF_ATTORNEY_PICTURE;
                MediaService::storeImage($vehicle, $file, $clearMedia, $collectionName);
            }

            if ($request->has('vehicle_profile_image')) {
                $file = $request->file('vehicle_profile_image');
                $clearMedia = false; // or true // // NO vehicle image remove, since it is the first time the vehicle is being stored
                $collectionName = Vehicle::VEHICLE_PROFILE_PICTURE;
                MediaService::storeImage($vehicle, $file, $clearMedia, $collectionName);
            }

            return VehicleResource::make($vehicle->load('media', 'vehicleName', 'address', 'supplier', 'driver', 'bank'));


            
        });

        return $var;
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
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 422);
            }


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
