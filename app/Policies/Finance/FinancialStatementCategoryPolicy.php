<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\FinancialStatementCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class FinancialStatementCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FinancialStatementCategory');
    }

    public function view(AuthUser $authUser, FinancialStatementCategory $financialStatementCategory): bool
    {
        return $authUser->can('View:FinancialStatementCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FinancialStatementCategory');
    }

    public function update(AuthUser $authUser, FinancialStatementCategory $financialStatementCategory): bool
    {
        return $authUser->can('Update:FinancialStatementCategory');
    }

    public function delete(AuthUser $authUser, FinancialStatementCategory $financialStatementCategory): bool
    {
        return $authUser->can('Delete:FinancialStatementCategory');
    }

    public function restore(AuthUser $authUser, FinancialStatementCategory $financialStatementCategory): bool
    {
        return $authUser->can('Restore:FinancialStatementCategory');
    }

    public function forceDelete(AuthUser $authUser, FinancialStatementCategory $financialStatementCategory): bool
    {
        return $authUser->can('ForceDelete:FinancialStatementCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FinancialStatementCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FinancialStatementCategory');
    }

    public function replicate(AuthUser $authUser, FinancialStatementCategory $financialStatementCategory): bool
    {
        return $authUser->can('Replicate:FinancialStatementCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FinancialStatementCategory');
    }

}