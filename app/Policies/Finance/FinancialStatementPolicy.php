<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\FinancialStatement;
use Illuminate\Auth\Access\HandlesAuthorization;

class FinancialStatementPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FinancialStatement');
    }

    public function view(AuthUser $authUser, FinancialStatement $financialStatement): bool
    {
        return $authUser->can('View:FinancialStatement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FinancialStatement');
    }

    public function update(AuthUser $authUser, FinancialStatement $financialStatement): bool
    {
        return $authUser->can('Update:FinancialStatement');
    }

    public function delete(AuthUser $authUser, FinancialStatement $financialStatement): bool
    {
        return $authUser->can('Delete:FinancialStatement');
    }

    public function restore(AuthUser $authUser, FinancialStatement $financialStatement): bool
    {
        return $authUser->can('Restore:FinancialStatement');
    }

    public function forceDelete(AuthUser $authUser, FinancialStatement $financialStatement): bool
    {
        return $authUser->can('ForceDelete:FinancialStatement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FinancialStatement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FinancialStatement');
    }

    public function replicate(AuthUser $authUser, FinancialStatement $financialStatement): bool
    {
        return $authUser->can('Replicate:FinancialStatement');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FinancialStatement');
    }

}