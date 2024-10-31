<?php

namespace App\Policies;

use App\Models\Constant;
use App\Models\Admin as User;
use App\Models\Permission;
use Illuminate\Auth\Access\Response;

class ConstantPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_CONSTANT)->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Constant $constant): bool
    {
        return $user->permissions()->where('permissions.title', Permission::SHOW_CONSTANT)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::CREATE_CONSTANT)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Constant $constant): bool
    {
        return $user->permissions()->where('permissions.title', Permission::EDIT_CONSTANT)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Constant $constant): bool
    {
        return $user->permissions()->where('permissions.title', Permission::DELETE_CONSTANT)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Constant $constant): bool
    {
        return $user->permissions()->where('permissions.title', Permission::RESTORE_CONSTANT)->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Constant $constant): bool
    {
        return false;
    }
}
