<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\MediaService;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\AdminResources\AdminResource;
use App\Http\Requests\Api\V1\AdminRequests\StoreAdminRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdateAdminRequest;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $this->authorize('viewAny', Admin::class);

        $admin = Admin::whereNotNull('id')->with('media', 'roles');
        
        if ($request->has('name')){
            FilteringService::filterByAllNames($request, $admin);
        }
        $adminData = $admin->paginate(FilteringService::getPaginate($request));

        return AdminResource::collection($adminData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdminRequest $request)
    {
        //
        $var = DB::transaction(function () use ($request) {

            $admin = Admin::create($request->validated());
            //
            if (!$admin) {
                return response()->json(['message' => 'Admin Create Failed'], 500);
            }


            $success = $admin->roles()->attach($request->input('role_ids'));
            //
            // this will not work B/C if the above operation is SUCCESSFUL it returns NULL to the $success variable, which is falsy in PHP
            // The attach method in Laravel returns null if the operation is successful. Therefore, checking if (!$success) might not work as expected since null is considered falsy in PHP.
            // if (!$success) {
            //     return response()->json(['message' => 'Role Attach Failed'], 500);
            // }

            

            if ($request->has('admin_profile_image')) {
                $file = $request->file('admin_profile_image');
                $clearMedia = false; // or true // // NO admin image remove, since it is the first time the admin is being stored
                $collectionName = Admin::ADMIN_PROFILE_PICTURE;
                MediaService::storeImage($admin, $file, $clearMedia, $collectionName);
            }

            return AdminResource::make($admin->load(['permissions', 'address', 'roles', 'media']));

        });

        return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin)
    {
        //
        $this->authorize('view', $admin);
        
        return AdminResource::make($admin->load(['permissions', 'address', 'roles', 'media']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdminRequest $request, Admin $admin)
    {
        //
        $var = DB::transaction(function () use ($request, $admin) {
            
            $success = $admin->update($request->validated());
            //
            if (!$success) {
                return response()->json(['message' => 'Admin Update Failed'], 500);
            }
            

            if ($request->has('role_ids')){
                $successTwo = $admin->roles()->sync($request->input('role_ids'));
                //
                // this will not work B/C if the above operation is SUCCESSFUL it returns NULL to the $success variable, which is falsy in PHP
                // The attach method in Laravel returns null if the operation is successful. Therefore, checking if (!$success) might not work as expected since null is considered falsy in PHP.
                // if (!$successTwo) {
                //     return response()->json(['message' => 'Role Sync Failed'], 500);
                // }
            }


            // if ($request->has('remove_image') && $request->input('remove_image', false)) {
            //     $admin->clearMediaCollection('images');
            // }

            // if ($request->has('profile_image')) {
            //     MediaService::storeImage($admin, $request->file('profile_image'));
            // }



            if ($request->has('admin_profile_image')) {
                $file = $request->file('admin_profile_image');
                $clearMedia = (isset($request['admin_profile_image_remove']) ? $request['admin_profile_image_remove'] : false);
                $collectionName = Admin::ADMIN_PROFILE_PICTURE;
                MediaService::storeImage($admin, $file, $clearMedia, $collectionName);
            }

            $updatedAdmin = Admin::find($admin->id);

            return AdminResource::make($updatedAdmin->load(['permissions', 'address', 'roles', 'media']));

        });

        return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        // $this->authorize('delete', $admin);
    }
}
