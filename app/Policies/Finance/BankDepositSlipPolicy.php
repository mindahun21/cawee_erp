<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\BankDepositSlip;
use Illuminate\Auth\Access\HandlesAuthorization;

class BankDepositSlipPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BankDepositSlip');
    }

    public function view(AuthUser $authUser, BankDepositSlip $bankDepositSlip): bool
    {
        return $authUser->can('View:BankDepositSlip');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BankDepositSlip');
    }

    public function update(AuthUser $authUser, BankDepositSlip $bankDepositSlip): bool
    {
        return $authUser->can('Update:BankDepositSlip');
    }

    public function delete(AuthUser $authUser, BankDepositSlip $bankDepositSlip): bool
    {
        return $authUser->can('Delete:BankDepositSlip');
    }

    public function restore(AuthUser $authUser, BankDepositSlip $bankDepositSlip): bool
    {
        return $authUser->can('Restore:BankDepositSlip');
    }

    public function forceDelete(AuthUser $authUser, BankDepositSlip $bankDepositSlip): bool
    {
        return $authUser->can('ForceDelete:BankDepositSlip');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BankDepositSlip');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BankDepositSlip');
    }

    public function replicate(AuthUser $authUser, BankDepositSlip $bankDepositSlip): bool
    {
        return $authUser->can('Replicate:BankDepositSlip');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BankDepositSlip');
    }

}