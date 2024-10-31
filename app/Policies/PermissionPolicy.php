<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Admin as User;
use Illuminate\Auth\Access\Response;

class PermissionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_PERMISSION)->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->permissions()->where('permissions.title', Permission::SHOW_PERMISSION)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // return $user->permissions()->where('permissions.title', Permission::CREATE_PERMISSION)->exists();
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Permission $permission): bool
    {
        // return $user->permissions()->where('permissions.title', Permission::EDIT_PERMISSION)->exists();
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Permission $permission): bool
    {
        // return $user->permissions()->where('permissions.title', Permission::DELETE_PERMISSION)->exists();
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Permission $permission): bool
    {
        // return $user->permissions()->where('permissions.title', Permission::RESTORE_PERMISSION)->exists();
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Permission $permission): bool
    {
        return false;
    }
}
