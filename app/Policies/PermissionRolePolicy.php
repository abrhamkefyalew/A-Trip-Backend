<?php

namespace App\Policies;

use App\Models\PermissionRole;
use App\Models\Admin as User;
use App\Models\Permission;
use Illuminate\Auth\Access\Response;

class PermissionRolePolicy
{

    public function sync(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::SYNC_PERMISSION_ROLE)->exists();
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_PERMISSION_ROLE)->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PermissionRole $permissionRole): bool
    {
        return $user->permissions()->where('permissions.title', Permission::SHOW_PERMISSION_ROLE)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::CREATE_PERMISSION_ROLE)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PermissionRole $permissionRole): bool
    {
        return $user->permissions()->where('permissions.title', Permission::EDIT_PERMISSION_ROLE)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PermissionRole $permissionRole): bool
    {
        return $user->permissions()->where('permissions.title', Permission::DELETE_PERMISSION_ROLE)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PermissionRole $permissionRole): bool
    {
        return $user->permissions()->where('permissions.title', Permission::RESTORE_PERMISSION_ROLE)->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PermissionRole $permissionRole): bool
    {
        return false;
    }
}
