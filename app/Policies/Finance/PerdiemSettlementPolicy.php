<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\PerdiemSettlement;
use Illuminate\Auth\Access\HandlesAuthorization;

class PerdiemSettlementPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PerdiemSettlement');
    }

    public function view(AuthUser $authUser, PerdiemSettlement $perdiemSettlement): bool
    {
        return $authUser->can('View:PerdiemSettlement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PerdiemSettlement');
    }

    public function update(AuthUser $authUser, PerdiemSettlement $perdiemSettlement): bool
    {
        return $authUser->can('Update:PerdiemSettlement');
    }

    public function delete(AuthUser $authUser, PerdiemSettlement $perdiemSettlement): bool
    {
        return $authUser->can('Delete:PerdiemSettlement');
    }

    public function restore(AuthUser $authUser, PerdiemSettlement $perdiemSettlement): bool
    {
        return $authUser->can('Restore:PerdiemSettlement');
    }

    public function forceDelete(AuthUser $authUser, PerdiemSettlement $perdiemSettlement): bool
    {
        return $authUser->can('ForceDelete:PerdiemSettlement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PerdiemSettlement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PerdiemSettlement');
    }

    public function replicate(AuthUser $authUser, PerdiemSettlement $perdiemSettlement): bool
    {
        return $authUser->can('Replicate:PerdiemSettlement');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PerdiemSettlement');
    }

}