<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\IncomeRegister;
use Illuminate\Auth\Access\HandlesAuthorization;

class IncomeRegisterPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IncomeRegister');
    }

    public function view(AuthUser $authUser, IncomeRegister $incomeRegister): bool
    {
        return $authUser->can('View:IncomeRegister');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IncomeRegister');
    }

    public function update(AuthUser $authUser, IncomeRegister $incomeRegister): bool
    {
        return $authUser->can('Update:IncomeRegister');
    }

    public function delete(AuthUser $authUser, IncomeRegister $incomeRegister): bool
    {
        return $authUser->can('Delete:IncomeRegister');
    }

    public function restore(AuthUser $authUser, IncomeRegister $incomeRegister): bool
    {
        return $authUser->can('Restore:IncomeRegister');
    }

    public function forceDelete(AuthUser $authUser, IncomeRegister $incomeRegister): bool
    {
        return $authUser->can('ForceDelete:IncomeRegister');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:IncomeRegister');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:IncomeRegister');
    }

    public function replicate(AuthUser $authUser, IncomeRegister $incomeRegister): bool
    {
        return $authUser->can('Replicate:IncomeRegister');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:IncomeRegister');
    }

}