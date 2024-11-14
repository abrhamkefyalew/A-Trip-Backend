<?php

namespace App\Policies;

use App\Models\ContractDetail;
use App\Models\Admin as User;
use App\Models\Permission;
use Illuminate\Auth\Access\Response;

class ContractDetailPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_CONTRACT)->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ContractDetail $contractDetail): bool
    {
        return $user->permissions()->where('permissions.title', Permission::SHOW_CONTRACT)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::CREATE_CONTRACT)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ContractDetail $contractDetail): bool
    {
        return $user->permissions()->where('permissions.title', Permission::EDIT_CONTRACT)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ContractDetail $contractDetail): bool
    {
        return $user->permissions()->where('permissions.title', Permission::DELETE_CONTRACT)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ContractDetail $contractDetail): bool
    {
        return $user->permissions()->where('permissions.title', Permission::RESTORE_CONTRACT)->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ContractDetail $contractDetail): bool
    {
        return false;
    }
}
