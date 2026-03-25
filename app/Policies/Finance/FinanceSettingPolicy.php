<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\FinanceSetting;
use Illuminate\Auth\Access\HandlesAuthorization;

class FinanceSettingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FinanceSetting');
    }

    public function view(AuthUser $authUser, FinanceSetting $financeSetting): bool
    {
        return $authUser->can('View:FinanceSetting');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FinanceSetting');
    }

    public function update(AuthUser $authUser, FinanceSetting $financeSetting): bool
    {
        return $authUser->can('Update:FinanceSetting');
    }

    public function delete(AuthUser $authUser, FinanceSetting $financeSetting): bool
    {
        return $authUser->can('Delete:FinanceSetting');
    }

    public function restore(AuthUser $authUser, FinanceSetting $financeSetting): bool
    {
        return $authUser->can('Restore:FinanceSetting');
    }

    public function forceDelete(AuthUser $authUser, FinanceSetting $financeSetting): bool
    {
        return $authUser->can('ForceDelete:FinanceSetting');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FinanceSetting');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FinanceSetting');
    }

    public function replicate(AuthUser $authUser, FinanceSetting $financeSetting): bool
    {
        return $authUser->can('Replicate:FinanceSetting');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FinanceSetting');
    }

}