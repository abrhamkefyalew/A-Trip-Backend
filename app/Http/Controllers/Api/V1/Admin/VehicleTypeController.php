<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\VehicleName;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\AdminRequests\StoreVehicleTypeRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateVehicleTypeRequest;
use App\Http\Resources\Api\V1\VehicleTypeResources\VehicleTypeResource;

class VehicleTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // do auth here
        // $this->authorize('viewAny', VehicleType::class);

        // scope should be used here
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
    public function store(StoreVehicleTypeRequest $request)
    {
        //
        $var = DB::transaction(function () use($request) {
            $vehicleType = VehicleType::create($request->validated());

            return VehicleTypeResource::make($vehicleType);
        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(VehicleType $vehicleType)
    {
        // $this->authorize('view', $vehicleType);
        
        return VehicleTypeResource::make($vehicleType->load('vehicleNames'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVehicleTypeRequest $request, VehicleType $vehicleType)
    {
        //
        $var = DB::transaction(function () use($request, $vehicleType) {
            $vehicleType->update($request->validated());

            return VehicleTypeResource::make($vehicleType);
        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VehicleType $vehicleType)
    {
        // $this->authorize('delete', $vehicleType);

        $var = DB::transaction(function () use ($vehicleType) {

            if (VehicleName::where('vehicle_type_id', $vehicleType->id)->exists()) {
                
                // this works
                // return response()->json([
                //     'message' => 'Cannot delete the vehicleType because it is in use by Vehicle Names category.',
                // ], 409);

                // this also works
                return response()->json([
                    'message' => 'Cannot delete the vehicleType because it is in use by Vehicle Names category.'
                ], Response::HTTP_CONFLICT);
            }

            $vehicleType->delete();

            return response()->json(true, 200);

        });

        return $var;
    }


    public function restore(string $id)
    {
        $vehicleType = VehicleType::withTrashed()->find($id);
        
        // $this->authorize('restore', $vehicleType);

        $var = DB::transaction(function () use ($vehicleType) {
            
            if (!$vehicleType) {
                abort(404);    
            }
    
            $vehicleType->restore();
    
            return response()->json(true, 200);

        });

        return $var;
        
    }


}
