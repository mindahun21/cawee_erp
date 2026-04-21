<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\DeclaredTax;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeclaredTaxPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DeclaredTax');
    }

    public function view(AuthUser $authUser, DeclaredTax $declaredTax): bool
    {
        return $authUser->can('View:DeclaredTax');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DeclaredTax');
    }

    public function update(AuthUser $authUser, DeclaredTax $declaredTax): bool
    {
        return $authUser->can('Update:DeclaredTax');
    }

    public function delete(AuthUser $authUser, DeclaredTax $declaredTax): bool
    {
        return $authUser->can('Delete:DeclaredTax');
    }

    public function restore(AuthUser $authUser, DeclaredTax $declaredTax): bool
    {
        return $authUser->can('Restore:DeclaredTax');
    }

    public function forceDelete(AuthUser $authUser, DeclaredTax $declaredTax): bool
    {
        return $authUser->can('ForceDelete:DeclaredTax');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DeclaredTax');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DeclaredTax');
    }

    public function replicate(AuthUser $authUser, DeclaredTax $declaredTax): bool
    {
        return $authUser->can('Replicate:DeclaredTax');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DeclaredTax');
    }

}