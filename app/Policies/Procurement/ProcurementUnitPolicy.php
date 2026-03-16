<?php

declare(strict_types=1);

namespace App\Policies\Procurement;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Procurement\ProcurementUnit;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcurementUnitPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProcurementUnit');
    }

    public function view(AuthUser $authUser, ProcurementUnit $procurementUnit): bool
    {
        return $authUser->can('View:ProcurementUnit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProcurementUnit');
    }

    public function update(AuthUser $authUser, ProcurementUnit $procurementUnit): bool
    {
        return $authUser->can('Update:ProcurementUnit');
    }

    public function delete(AuthUser $authUser, ProcurementUnit $procurementUnit): bool
    {
        return $authUser->can('Delete:ProcurementUnit');
    }

    public function restore(AuthUser $authUser, ProcurementUnit $procurementUnit): bool
    {
        return $authUser->can('Restore:ProcurementUnit');
    }

    public function forceDelete(AuthUser $authUser, ProcurementUnit $procurementUnit): bool
    {
        return $authUser->can('ForceDelete:ProcurementUnit');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProcurementUnit');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProcurementUnit');
    }

    public function replicate(AuthUser $authUser, ProcurementUnit $procurementUnit): bool
    {
        return $authUser->can('Replicate:ProcurementUnit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProcurementUnit');
    }

}