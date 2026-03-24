<?php

declare(strict_types=1);

namespace App\Policies\Procurement;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Procurement\SupplierReturn;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierReturnPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SupplierReturn');
    }

    public function view(AuthUser $authUser, SupplierReturn $supplierReturn): bool
    {
        return $authUser->can('View:SupplierReturn');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SupplierReturn');
    }

    public function update(AuthUser $authUser, SupplierReturn $supplierReturn): bool
    {
        return $authUser->can('Update:SupplierReturn');
    }

    public function delete(AuthUser $authUser, SupplierReturn $supplierReturn): bool
    {
        return $authUser->can('Delete:SupplierReturn');
    }

    public function restore(AuthUser $authUser, SupplierReturn $supplierReturn): bool
    {
        return $authUser->can('Restore:SupplierReturn');
    }

    public function forceDelete(AuthUser $authUser, SupplierReturn $supplierReturn): bool
    {
        return $authUser->can('ForceDelete:SupplierReturn');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SupplierReturn');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SupplierReturn');
    }

    public function replicate(AuthUser $authUser, SupplierReturn $supplierReturn): bool
    {
        return $authUser->can('Replicate:SupplierReturn');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SupplierReturn');
    }

}