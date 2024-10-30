<?php

namespace App\Policies;

use App\Models\Bank;
use App\Models\Admin as User;
use App\Models\Permission;
use Illuminate\Auth\Access\Response;

class BankPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_BANK)->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Bank $bank): bool
    {
        return $user->permissions()->where('permissions.title', Permission::SHOW_BANK)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::CREATE_BANK)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Bank $bank): bool
    {
        return $user->permissions()->where('permissions.title', Permission::EDIT_BANK)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Bank $bank): bool
    {
        return $user->permissions()->where('permissions.title', Permission::DELETE_BANK)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Bank $bank): bool
    {
        return $user->permissions()->where('permissions.title', Permission::RESTORE_BANK)->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Bank $bank): bool
    {
        return false;
    }
}
