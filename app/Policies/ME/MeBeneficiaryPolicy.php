<?php

declare(strict_types=1);

namespace App\Policies\ME;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ME\MeBeneficiary;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeBeneficiaryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MeBeneficiary');
    }

    public function view(AuthUser $authUser, MeBeneficiary $meBeneficiary): bool
    {
        return $authUser->can('View:MeBeneficiary');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MeBeneficiary');
    }

    public function update(AuthUser $authUser, MeBeneficiary $meBeneficiary): bool
    {
        return $authUser->can('Update:MeBeneficiary');
    }

    public function delete(AuthUser $authUser, MeBeneficiary $meBeneficiary): bool
    {
        return $authUser->can('Delete:MeBeneficiary');
    }

    public function restore(AuthUser $authUser, MeBeneficiary $meBeneficiary): bool
    {
        return $authUser->can('Restore:MeBeneficiary');
    }

    public function forceDelete(AuthUser $authUser, MeBeneficiary $meBeneficiary): bool
    {
        return $authUser->can('ForceDelete:MeBeneficiary');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MeBeneficiary');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MeBeneficiary');
    }

    public function replicate(AuthUser $authUser, MeBeneficiary $meBeneficiary): bool
    {
        return $authUser->can('Replicate:MeBeneficiary');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MeBeneficiary');
    }

}