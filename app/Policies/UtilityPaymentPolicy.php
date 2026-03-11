<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\UtilityPayment;
use Illuminate\Auth\Access\HandlesAuthorization;

class UtilityPaymentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:UtilityPayment');
    }

    public function view(AuthUser $authUser, UtilityPayment $utilityPayment): bool
    {
        return $authUser->can('View:UtilityPayment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:UtilityPayment');
    }

    public function update(AuthUser $authUser, UtilityPayment $utilityPayment): bool
    {
        return $authUser->can('Update:UtilityPayment');
    }

    public function delete(AuthUser $authUser, UtilityPayment $utilityPayment): bool
    {
        return $authUser->can('Delete:UtilityPayment');
    }

    public function restore(AuthUser $authUser, UtilityPayment $utilityPayment): bool
    {
        return $authUser->can('Restore:UtilityPayment');
    }

    public function forceDelete(AuthUser $authUser, UtilityPayment $utilityPayment): bool
    {
        return $authUser->can('ForceDelete:UtilityPayment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:UtilityPayment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:UtilityPayment');
    }

    public function replicate(AuthUser $authUser, UtilityPayment $utilityPayment): bool
    {
        return $authUser->can('Replicate:UtilityPayment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:UtilityPayment');
    }

}