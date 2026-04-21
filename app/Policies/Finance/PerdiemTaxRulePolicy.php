<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\PerdiemTaxRule;
use Illuminate\Auth\Access\HandlesAuthorization;

class PerdiemTaxRulePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PerdiemTaxRule');
    }

    public function view(AuthUser $authUser, PerdiemTaxRule $perdiemTaxRule): bool
    {
        return $authUser->can('View:PerdiemTaxRule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PerdiemTaxRule');
    }

    public function update(AuthUser $authUser, PerdiemTaxRule $perdiemTaxRule): bool
    {
        return $authUser->can('Update:PerdiemTaxRule');
    }

    public function delete(AuthUser $authUser, PerdiemTaxRule $perdiemTaxRule): bool
    {
        return $authUser->can('Delete:PerdiemTaxRule');
    }

    public function restore(AuthUser $authUser, PerdiemTaxRule $perdiemTaxRule): bool
    {
        return $authUser->can('Restore:PerdiemTaxRule');
    }

    public function forceDelete(AuthUser $authUser, PerdiemTaxRule $perdiemTaxRule): bool
    {
        return $authUser->can('ForceDelete:PerdiemTaxRule');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PerdiemTaxRule');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PerdiemTaxRule');
    }

    public function replicate(AuthUser $authUser, PerdiemTaxRule $perdiemTaxRule): bool
    {
        return $authUser->can('Replicate:PerdiemTaxRule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PerdiemTaxRule');
    }

}