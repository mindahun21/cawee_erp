<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\AccountingPeriod;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountingPeriodPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AccountingPeriod');
    }

    public function view(AuthUser $authUser, AccountingPeriod $accountingPeriod): bool
    {
        return $authUser->can('View:AccountingPeriod');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AccountingPeriod');
    }

    public function update(AuthUser $authUser, AccountingPeriod $accountingPeriod): bool
    {
        return $authUser->can('Update:AccountingPeriod');
    }

    public function delete(AuthUser $authUser, AccountingPeriod $accountingPeriod): bool
    {
        return $authUser->can('Delete:AccountingPeriod');
    }

    public function restore(AuthUser $authUser, AccountingPeriod $accountingPeriod): bool
    {
        return $authUser->can('Restore:AccountingPeriod');
    }

    public function forceDelete(AuthUser $authUser, AccountingPeriod $accountingPeriod): bool
    {
        return $authUser->can('ForceDelete:AccountingPeriod');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AccountingPeriod');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AccountingPeriod');
    }

    public function replicate(AuthUser $authUser, AccountingPeriod $accountingPeriod): bool
    {
        return $authUser->can('Replicate:AccountingPeriod');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AccountingPeriod');
    }

}