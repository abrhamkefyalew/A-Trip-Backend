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

        return RoleResource::collection($roles->paginate(FilteringService::getPaginate($request)));
    }

    public static function store($validatedData)
    {
        //
    }

    public static function update($validatedData, Role $role)
    {
        //
    }

    // public static function getPermissionGroupsByAdmin(Admin $admin)
    // {
    //     $permissionGroups = collect();

    //     foreach ($admin->roles as $role) {
    //         $permissionGroups = $permissionGroups->merge(self::getPermissionGroups($role));
    //     }

    //     return $permissionGroups;
    // }

    // public static function getPermissionGroups(Role $role)
    // {
    //     $permissionGroups = [];

    //     foreach (PermissionGroup::all(['id', 'title']) as $permissionGroup) {
    //         $pass = true;
    //         foreach ($permissionGroup->permissions as $permission) {
    //             if (! $role->permissions->find($permission)) {
    //                 $pass = false;
    //                 break;
    //             }
    //         }

    //         if ($pass) {
    //             $permissionGroups[] = $permissionGroup->unsetRelation('permissions');
    //         }
    //     }

    //     return collect($permissionGroups);
    // }
}
