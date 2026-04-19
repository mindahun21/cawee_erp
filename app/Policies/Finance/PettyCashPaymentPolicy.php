<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\PettyCashPayment;
use Illuminate\Auth\Access\HandlesAuthorization;

class PettyCashPaymentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PettyCashPayment');
    }

    public function view(AuthUser $authUser, PettyCashPayment $pettyCashPayment): bool
    {
        return $authUser->can('View:PettyCashPayment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PettyCashPayment');
    }

    public function update(AuthUser $authUser, PettyCashPayment $pettyCashPayment): bool
    {
        return $authUser->can('Update:PettyCashPayment');
    }

    public function delete(AuthUser $authUser, PettyCashPayment $pettyCashPayment): bool
    {
        return $authUser->can('Delete:PettyCashPayment');
    }

    public function restore(AuthUser $authUser, PettyCashPayment $pettyCashPayment): bool
    {
        return $authUser->can('Restore:PettyCashPayment');
    }

    public function forceDelete(AuthUser $authUser, PettyCashPayment $pettyCashPayment): bool
    {
        return $authUser->can('ForceDelete:PettyCashPayment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PettyCashPayment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PettyCashPayment');
    }

    public function replicate(AuthUser $authUser, PettyCashPayment $pettyCashPayment): bool
    {
        return $authUser->can('Replicate:PettyCashPayment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PettyCashPayment');
    }

}