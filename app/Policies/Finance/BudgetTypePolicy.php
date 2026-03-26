<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\BudgetType;
use Illuminate\Auth\Access\HandlesAuthorization;

class BudgetTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BudgetType');
    }

    public function view(AuthUser $authUser, BudgetType $budgetType): bool
    {
        return $authUser->can('View:BudgetType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BudgetType');
    }

    public function update(AuthUser $authUser, BudgetType $budgetType): bool
    {
        return $authUser->can('Update:BudgetType');
    }

    public function delete(AuthUser $authUser, BudgetType $budgetType): bool
    {
        return $authUser->can('Delete:BudgetType');
    }

    public function restore(AuthUser $authUser, BudgetType $budgetType): bool
    {
        return $authUser->can('Restore:BudgetType');
    }

    public function forceDelete(AuthUser $authUser, BudgetType $budgetType): bool
    {
        return $authUser->can('ForceDelete:BudgetType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BudgetType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BudgetType');
    }

    public function replicate(AuthUser $authUser, BudgetType $budgetType): bool
    {
        return $authUser->can('Replicate:BudgetType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BudgetType');
    }

}