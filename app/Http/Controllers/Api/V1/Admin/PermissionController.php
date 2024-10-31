<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Permission;
use App\Http\Controllers\Controller;
use App\Services\Api\V1\FilteringService;
use App\Http\Requests\Api\V1\AdminRequests\StorePermissionRequest;
use App\Http\Requests\Api\V1\AdminRequests\UpdatePermissionRequest;
use App\Http\Resources\Api\V1\PermissionResources\PermissionResource;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Permission::class);

        // scope should be used here
        if (isset($request['paginate'])) {
            if ($request['paginate'] == "all"){
                $permission = Permission::get();
            }
            else {
                $permission = Permission::paginate(FilteringService::getPaginate($request));
            }
        } else {
            $permission = Permission::with(['roles'])->get();
        }


        return PermissionResource::collection($permission);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePermissionRequest $request)
    {
        //
        // $var = DB::transaction(function () {
        //
        //    // currently NOT allowed by the business logic
        //
        // });

        // return $var;
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        $this->authorize('view', $permission);

        // // to use this we need additional implementation in the PermissionResource                   // need to be hard if used
        // return PermissionResource::make($permission->load(['admins', 'roles'])->loadCount('roles')); // if use need to be tested thoroughly

        return PermissionResource::make($permission->load(['admins', 'roles']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        //
        // $var = DB::transaction(function () {
        //
        //    // currently NOT allowed by the business logic
        //    
        // });

        // return $var;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        //
    }
}
