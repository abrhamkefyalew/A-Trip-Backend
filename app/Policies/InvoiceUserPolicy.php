<?php

namespace App\Policies;

use App\Models\InvoiceUser;
use App\Models\Admin as User;
use App\Models\Permission;
use Illuminate\Auth\Access\Response;

class InvoiceUserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_INVOICE)->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InvoiceUser $invoiceUser): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_INVOICE)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_INVOICE)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InvoiceUser $invoiceUser): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_INVOICE)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InvoiceUser $invoiceUser): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_INVOICE)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InvoiceUser $invoiceUser): bool
    {
        return $user->permissions()->where('permissions.title', Permission::INDEX_INVOICE)->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InvoiceUser $invoiceUser): bool
    {
        return false;
    }
}
