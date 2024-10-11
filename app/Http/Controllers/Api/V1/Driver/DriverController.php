<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
use App\Http\Resources\Api\V1\DriverResources\DriverResource;
use App\Http\Requests\Api\V1\DriverRequests\UpdateDriverRequest;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
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
    public function show(Driver $driver)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDriverRequest $request, Driver $driver)
    {
        $var = DB::transaction(function () use ($request, $driver) {

            $user = auth()->user();
            $driverLoggedIn = Driver::find($user->id);

            
            if ($driverLoggedIn->id != $driver->id) {
                
                return response()->json(['message' => 'invalid Driver is selected or Requested. Deceptive request Aborted.'], 401);
            }
            
            
            $success = $driver->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Update Failed'], 422);
            }
            

            if ($request->has('country') || $request->has('city')) {
                if ($driver->address) {
                    $driver->address()->update([
                        'country' => $request->input('country'),
                        'city' => $request->input('city'),
                    ]);
                } else {
                    $driver->address()->create([
                        'country' => $request->input('country'),
                        'city' => $request->input('city'),
                    ]);
                }
            }



            // MEDIA CODE SECTION
            // REMEMBER = (clearMedia) ALL media should NOT be Cleared at once, media should be cleared by id, like one picture. so the whole collection should NOT be cleared using $clearMedia the whole collection // check abrham samson // remember
            //
            if ($request->has('driver_license_front_image')) {
                $file = $request->file('driver_license_front_image');
                $clearMedia = $request->input('driver_license_front_image_remove', false);
                $collectionName = Driver::DRIVER_LICENSE_FRONT_PICTURE;
                MediaService::storeImage($driver, $file, $clearMedia, $collectionName);
            }

            if ($request->has('driver_license_back_image')) {
                $file = $request->file('driver_license_back_image');
                $clearMedia = $request->input('driver_license_back_image_remove', false);
                $collectionName = Driver::DRIVER_LICENSE_BACK_PICTURE;
                MediaService::storeImage($driver, $file, $clearMedia, $collectionName);
            }
            
            if ($request->has('driver_id_front_image')) {
                $file = $request->file('driver_id_front_image');
                $clearMedia = $request->input('driver_id_front_image_remove', false);
                $collectionName = Driver::DRIVER_ID_FRONT_PICTURE;
                MediaService::storeImage($driver, $file, $clearMedia, $collectionName);
            }

            if ($request->has('driver_id_back_image')) {
                $file = $request->file('driver_id_back_image');
                $clearMedia = $request->input('driver_id_back_image_remove', false); 
                $collectionName = Driver::DRIVER_ID_BACK_PICTURE;
                MediaService::storeImage($driver, $file, $clearMedia, $collectionName);
            }

            if ($request->has('driver_profile_image')) {
                $file = $request->file('driver_profile_image');
                $clearMedia = (isset($request['driver_profile_image_remove']) ? $request['driver_profile_image_remove'] : false);
                $collectionName = Driver::DRIVER_PROFILE_PICTURE;
                MediaService::storeImage($driver, $file, $clearMedia, $collectionName);
            }

            
            $updatedDriver = Driver::find($driver->id);

            return DriverResource::make($updatedDriver->load('media', 'address', 'vehicle'));

        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Driver $driver)
    {
        //
    }
}
