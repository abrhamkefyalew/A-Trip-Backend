<?php

namespace App\Services\Api\V1\Admin;

use App\Models\Role;
use App\Models\Admin;
use App\Services\Api\V1\FilteringService;
use App\Http\Resources\Api\V1\RoleResources\RoleResource;

class RoleService
{
    public static function index($request)
    {
        $roles = Role::whereNotNull('id');

        if ($request->has('admins')) {
            $roles = $roles->with(['admins']);
        }

        if ($request->has('permissions')) {
            $roles = $roles->with(['permissions']);
        }

        $roles->withCount('admins');

        FilteringService::filterByTitle($request, $roles);

        FilteringService::addTrashed($request, $roles);

        $roleData = $roles->paginate(FilteringService::getPaginate($request));
        
        return RoleResource::collection($roleData);
    }

    public static function store($validatedData)
    {
        //
    }

    public static function update($validatedData, Role $role)
    {
        //
    }


}
