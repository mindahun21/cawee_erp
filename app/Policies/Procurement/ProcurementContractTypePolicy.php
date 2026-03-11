<?php

declare(strict_types=1);

namespace App\Policies\Procurement;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Procurement\ProcurementContractType;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcurementContractTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProcurementContractType');
    }

    public function view(AuthUser $authUser, ProcurementContractType $procurementContractType): bool
    {
        return $authUser->can('View:ProcurementContractType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProcurementContractType');
    }

    public function update(AuthUser $authUser, ProcurementContractType $procurementContractType): bool
    {
        return $authUser->can('Update:ProcurementContractType');
    }

    public function delete(AuthUser $authUser, ProcurementContractType $procurementContractType): bool
    {
        return $authUser->can('Delete:ProcurementContractType');
    }

    public function restore(AuthUser $authUser, ProcurementContractType $procurementContractType): bool
    {
        return $authUser->can('Restore:ProcurementContractType');
    }

    public function forceDelete(AuthUser $authUser, ProcurementContractType $procurementContractType): bool
    {
        return $authUser->can('ForceDelete:ProcurementContractType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProcurementContractType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProcurementContractType');
    }

    public function replicate(AuthUser $authUser, ProcurementContractType $procurementContractType): bool
    {
        return $authUser->can('Replicate:ProcurementContractType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProcurementContractType');
    }

}