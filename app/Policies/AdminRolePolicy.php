<?php

namespace App\Policies;

use App\Models\Admin as User;
use App\Models\AdminRole;
use App\Models\Permission;
use Illuminate\Auth\Access\Response;

class AdminRolePolicy
{

    public function sync(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::SYNC_ADMIN_ROLE)->exists();
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AdminRole $adminRole): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AdminRole $adminRole): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AdminRole $adminRole): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AdminRole $adminRole): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AdminRole $adminRole): bool
    {
        return false;
    }
}
