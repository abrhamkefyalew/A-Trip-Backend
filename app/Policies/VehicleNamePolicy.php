<?php

namespace App\Policies;

use App\Models\VehicleName;
use App\Models\Admin as User;
use App\Models\Permission;
use Illuminate\Auth\Access\Response;

class VehicleNamePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_VEHICLE_NAME)->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VehicleName $vehicleName): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_VEHICLE_NAME)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_VEHICLE_NAME)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VehicleName $vehicleName): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_VEHICLE_NAME)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VehicleName $vehicleName): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_VEHICLE_NAME)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VehicleName $vehicleName): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_VEHICLE_NAME)->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VehicleName $vehicleName): bool
    {
        return false;
    }
}
