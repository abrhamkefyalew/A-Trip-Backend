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

            $admin->roles()->attach($request->input('role_ids'));

            if ($request->has('remove_image') && $request->input('remove_image', false)) {
                $admin->clearMediaCollection('images');
            }

            if ($request->has('profile_image')) {
                MediaService::storeImage($admin, $request->file('profile_image'));
            }

            return AdminResource::make($admin->load('roles'));
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
            
            $admin->update($request->validated());

            if ($request->has('role_ids')){
                $admin->roles()->sync($request->input('role_ids'));
            }


            if ($request->has('remove_image') && $request->input('remove_image', false)) {
                $admin->clearMediaCollection('images');
            }

            if ($request->has('profile_image')) {
                MediaService::storeImage($admin, $request->file('profile_image'));
            }

            return AdminResource::make($admin->load('roles'));

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
