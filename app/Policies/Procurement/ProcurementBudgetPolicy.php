<?php

declare(strict_types=1);

namespace App\Policies\Procurement;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Procurement\ProcurementBudget;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcurementBudgetPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProcurementBudget');
    }

    public function view(AuthUser $authUser, ProcurementBudget $procurementBudget): bool
    {
        return $authUser->can('View:ProcurementBudget');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProcurementBudget');
    }

    public function update(AuthUser $authUser, ProcurementBudget $procurementBudget): bool
    {
        return $authUser->can('Update:ProcurementBudget');
    }

    public function delete(AuthUser $authUser, ProcurementBudget $procurementBudget): bool
    {
        return $authUser->can('Delete:ProcurementBudget');
    }

    public function restore(AuthUser $authUser, ProcurementBudget $procurementBudget): bool
    {
        return $authUser->can('Restore:ProcurementBudget');
    }

    public function forceDelete(AuthUser $authUser, ProcurementBudget $procurementBudget): bool
    {
        return $authUser->can('ForceDelete:ProcurementBudget');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProcurementBudget');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProcurementBudget');
    }

    public function replicate(AuthUser $authUser, ProcurementBudget $procurementBudget): bool
    {
        return $authUser->can('Replicate:ProcurementBudget');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProcurementBudget');
    }

}