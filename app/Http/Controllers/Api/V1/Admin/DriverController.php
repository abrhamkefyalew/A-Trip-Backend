<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\DriverResources\DriverResource;
use App\Http\Requests\Api\V1\AdminRequests\StoreDriverRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateDriverRequest;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Driver::class);

        $drivers = Driver::whereNotNull('id');

        $driverData = $drivers->with('media')->latest()->paginate(FilteringService::getPaginate($request));

        return DriverResource::collection($driverData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDriverRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {
            
            $driver = Driver::create([
                'first_name' => $request['first_name'],
                'last_name' => $request['last_name'],
                'email' => $request['email'],
                'phone_number' => $request['phone_number'],
                'is_active' => (int) (isset($request['is_active']) ? $request['is_active'] : 1), // this works
                'is_available' => (int) $request->input('is_available', 1), // this works also
            ]);


            if ($request->has('country') || $request->has('city')) {
                $driver->address()->create([
                    'country' => $request->input('country'),
                    'city' => $request->input('city'),
                ]);
            }

            
            // NO driver image remove, since it is the first time the driver is being stored
            // also use the MediaService class to remove image


            if ($request->has('driver_license_front_image')) {
                $file = $request->file('driver_license_front_image');
                $clearMedia = false; // or true // // NO driver image remove, since it is the first time the driver is being stored
                $collectionName = Driver::DRIVER_LICENSE_FRONT_PICTURE;
                MediaService::storeImage($driver, $file, $clearMedia, $collectionName);
            }

            if ($request->has('driver_license_back_image')) {
                $file = $request->file('driver_license_back_image');
                $clearMedia = false; // or true // // NO driver image remove, since it is the first time the driver is being stored 
                $collectionName = Driver::DRIVER_LICENSE_BACK_PICTURE;
                MediaService::storeImage($driver, $file, $clearMedia, $collectionName);
            }
            
            if ($request->has('driver_id_front_image')) {
                $file = $request->file('driver_id_front_image');
                $clearMedia = false; // or true // // NO driver image remove, since it is the first time the driver is being stored
                $collectionName = Driver::DRIVER_ID_FRONT_PICTURE;
                MediaService::storeImage($driver, $file, $clearMedia, $collectionName);
            }

            if ($request->has('driver_id_back_image')) {
                $file = $request->file('driver_id_back_image');
                $clearMedia = false; // or true // // NO driver image remove, since it is the first time the driver is being stored 
                $collectionName = Driver::DRIVER_ID_BACK_PICTURE;
                MediaService::storeImage($driver, $file, $clearMedia, $collectionName);
            }

            if ($request->has('driver_profile_image')) {
                $file = $request->file('driver_profile_image');
                $clearMedia = false; // or true // // NO driver image remove, since it is the first time the driver is being stored
                $collectionName = Driver::DRIVER_PROFILE_PICTURE;
                MediaService::storeImage($driver, $file, $clearMedia, $collectionName);
            }


            return DriverResource::make($driver->load('vehicle', 'media', 'address'));


        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Driver $driver)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDriverRequest $request, Driver $driver)
    {
        //
        // $var = DB::transaction(function () {
            
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Driver $driver)
    {
        //
    }
}
