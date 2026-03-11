<?php


namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ContractType;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ContractType');
    }

    public function view(AuthUser $authUser, ContractType $record): bool
    {
        return $authUser->can('View:ContractType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ContractType');
    }

    public function update(AuthUser $authUser, ContractType $record): bool
    {
        return $authUser->can('Update:ContractType');
    }

    public function delete(AuthUser $authUser, ContractType $record): bool
    {
        return $authUser->can('Delete:ContractType');
    }

    public function restore(AuthUser $authUser, ContractType $record): bool
    {
        return $authUser->can('Restore:ContractType');
    }

    public function forceDelete(AuthUser $authUser, ContractType $record): bool
    {
        return $authUser->can('ForceDelete:ContractType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ContractType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ContractType');
    }

    public function replicate(AuthUser $authUser, ContractType $record): bool
    {
        return $authUser->can('Replicate:ContractType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ContractType');
    }
}

