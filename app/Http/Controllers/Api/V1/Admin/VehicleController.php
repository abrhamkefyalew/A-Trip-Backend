<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Bid;
use App\Models\Order;
use App\Models\Vehicle;
use App\Models\OrderUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\AdminRequests\StoreVehicleRequest;
use App\Http\Resources\Api\V1\VehicleResources\VehicleResource;
use App\Http\Requests\Api\V1\AdminRequests\UpdateVehicleRequest;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Vehicle::class);

        $vehicles = Vehicle::whereNotNull('id');
        
        // use Filtering service OR Scope to do this
        if ($request->has('supplier_id_search')) {
            if (isset($request['supplier_id_search'])) {
                $supplierId = $request['supplier_id_search'];

                $vehicles = $vehicles->where('supplier_id', $supplierId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }
        }
        if ($request->has('driver_id_search')) {
            if (isset($request['driver_id_search'])) {
                $driverId = $request['driver_id_search'];

                $vehicles = $vehicles->where('driver_id', $driverId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }
        }
        if ($request->has('vehicle_name_id_search')) {
            if (isset($request['vehicle_name_id_search'])) {
                $vehicleNameId = $request['vehicle_name_id_search'];

                $vehicles = $vehicles->where('vehicle_name_id', $vehicleNameId);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }
        }
        if ($request->has('with_driver_search')) {
            if (isset($request['with_driver_search'])) {
                $withDriverBool = $request['with_driver_search'];

                $vehicles = $vehicles->where('with_driver', $withDriverBool);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }
        }
        if ($request->has('plate_number_search')) {
            if (isset($request['plate_number_search'])) {
                $plateNumber = $request['plate_number_search'];

                $vehicles = $vehicles->where('plate_number', $plateNumber);
            } 
            else {
                return response()->json(['message' => 'Required parameter missing, Parameter missing or value not set.'], 400);
            }
        }
    
        
        

        $vehicleData = $vehicles->with('media', 'vehicleName', 'address', 'supplier', 'driver', 'bank')->latest()->paginate(FilteringService::getPaginate($request));

        return VehicleResource::collection($vehicleData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVehicleRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {

            // ask samson
            // check the sent with_driver in the request as the following and its consequences
            // if "with_driver" = 0 , driver_id must NOT come in the request, otherwise = I will return ERROR 
            // if "with_driver" = 1 , driver_id MUST also come in the request, otherwise = I will return ERROR
            // should we return error for such requests.
            if ($request['with_driver'] == 0) {
                if ($request->has('driver_id')) {
                    return response()->json(['message' => 'request can NOT contain a driver_id. You have set with_driver = 0, so driver_id should NOT be included your request.'], 422);
                }
                // if ($request['driver_id'] !== null) {
                //     return response()->json(['message' => 'you can NOT set a driver_id in the request. You have set with_driver = 0, so driver_id must be null in your request.'], 422);
                // }
            }
            if ($request['with_driver'] == 1) {
                if (!$request->has('driver_id')) {
                    return response()->json(['message' => 'request missing driver_id. You have set with_driver = 1, so you must Provide driver_id for your vehicle with your request.'], 422);
                }
                if ($request['driver_id'] === null) {
                    return response()->json(['message' => 'driver_id value is NOT set in the request. You have set with_driver = 1, so you must Provide driver_id for your vehicle with your request.'], 422);
                }
            }

            
            $vehicle = Vehicle::create([
                'vehicle_name_id' => $request['vehicle_name_id'],
                'supplier_id' => $request['supplier_id'],
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

            return VehicleResource::make($vehicle->load('media', 'vehicleName', 'address', 'supplier', 'driver', 'bank', 'bids'));


            
        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle)
    {
        // $this->authorize('view', $vehicle);
        
        return VehicleResource::make($vehicle->load('media', 'vehicleName', 'address', 'supplier', 'driver', 'bank', 'bids'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        // TODO
            // to update the driver_id for vehicle, // since the driver_id is unique in vehicles table, 
            //         1. the Supplier should detach the driver from his previously owned vehicle, making the driver_id=NULL in vehicles table, 
            //                     for detach we should make a separate api // under supplier routes
            //         2. once the Supplier detached the driver from his previous vehicle using the separate api for detach,  
            //         3. then the Supplier can send an ATTACH request with the driver_id and vehicle_id
            //                     for attach we should make a separate api
            // 
            // NOTE (IMPORTANT):
            //         DETACH 
            //                 - when the Supplier sends detach request to detach a driver from a vehicle,   
            //                             the vehicle that the driver is Already paired with,    MUST be owned by the Supplier_id who is sending the detach request,  otherwise he is invading other suppliers data
            //         ATTACH
            //                 - when the Supplier sends attach request to attach a driver with a vehicle,
            //                             the vehicle that the driver is going to be paired with,  MUST be owned by the Supplier_id who is sending the attach request,  otherwise he is invading other suppliers data
            //     so the DETACH and ATTACH can only be done within the Supplier_id who is sending this two requests



        //
        $var = DB::transaction(function () use ($request, $vehicle) {
            
            $success = $vehicle->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 500);
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
            // REMEMBER = (clearMedia) ALL media should NOT be Cleared at once, media should be cleared by id, like one picture. so the whole collection should NOT be cleared using $clearMedia the whole collection // check abrham samson // remember
            //
            if ($request->has('vehicle_libre_image')) {
                $file = $request->file('vehicle_libre_image');
                $clearMedia = $request->input('vehicle_libre_image_remove', false); 
                $collectionName = Vehicle::VEHICLE_LIBRE_PICTURE;
                MediaService::storeImage($vehicle, $file, $clearMedia, $collectionName);
            }
            
            if ($request->has('vehicle_third_person_image')) {
                $file = $request->file('vehicle_third_person_image');
                $clearMedia = $request->input('vehicle_third_person_image_remove', false);
                $collectionName = Vehicle::VEHICLE_THIRD_PERSON_PICTURE;
                MediaService::storeImage($vehicle, $file, $clearMedia, $collectionName);
            }

            if ($request->has('vehicle_power_of_attorney_image')) {
                $file = $request->file('vehicle_power_of_attorney_image');
                $clearMedia = $request->input('vehicle_power_of_attorney_image_remove', false);
                $collectionName = Vehicle::VEHICLE_POWER_OF_ATTORNEY_PICTURE;
                MediaService::storeImage($vehicle, $file, $clearMedia, $collectionName);
            }

            if ($request->has('vehicle_profile_image')) {
                $file = $request->file('vehicle_profile_image');
                $clearMedia = (isset($request['vehicle_profile_image_remove']) ? $request['vehicle_profile_image_remove'] : false);
                $collectionName = Vehicle::VEHICLE_PROFILE_PICTURE;
                MediaService::storeImage($vehicle, $file, $clearMedia, $collectionName);
            }


            $updatedVehicle = Vehicle::find($vehicle->id);

            return VehicleResource::make($updatedVehicle->load('media', 'vehicleName', 'address', 'supplier', 'driver', 'bank', 'bids'));

            
        });

        return $var;
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        // $this->authorize('delete', $vehicle);

        $var = DB::transaction(function () use ($vehicle) {

            if (Order::where('vehicle_id', $vehicle->id)->exists()) {
                
                // this works
                // return response()->json([
                //     'message' => 'Cannot delete the vehicle because it is in use by organization Orders.',
                // ], 409);

                // this also works
                return response()->json([
                    'message' => 'Cannot delete the vehicle because it is in use by organization Orders.'
                ], Response::HTTP_CONFLICT);
            }

            if (Bid::where('vehicle_id', $vehicle->id)->exists()) {
                
                // this works
                // return response()->json([
                //     'message' => 'Cannot delete the vehicle because it is in use by Bids.',
                // ], 409);

                // this also works
                return response()->json([
                    'message' => 'Cannot delete the vehicle because it is in use by Bids.'
                ], Response::HTTP_CONFLICT);
            }

            if (OrderUser::where('vehicle_id', $vehicle->id)->exists()) {
                
                // this works
                // return response()->json([
                //     'message' => 'Cannot delete the vehicle because it is in use by individual customer Orders.',
                // ], 409);

                // this also works
                return response()->json([
                    'message' => 'Cannot delete the vehicle because it is in use by individual customer Orders.'
                ], Response::HTTP_CONFLICT);
            }

            $vehicle->delete();

            return response()->json(true, 200);

        });

        return $var;
    }


    public function restore(string $id)
    {
        $vehicle = Vehicle::withTrashed()->find($id);

        // $this->authorize('restore', $vehicle);

        $var = DB::transaction(function () use ($vehicle) {
            
            if (!$vehicle) {
                abort(404);    
            }
    
            $vehicle->restore();
    
            return response()->json(true, 200);

        });

        return $var;
        
    }

    
}
