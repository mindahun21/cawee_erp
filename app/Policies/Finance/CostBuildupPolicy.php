<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\CostBuildup;
use Illuminate\Auth\Access\HandlesAuthorization;

class CostBuildupPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CostBuildup');
    }

    public function view(AuthUser $authUser, CostBuildup $costBuildup): bool
    {
        return $authUser->can('View:CostBuildup');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CostBuildup');
    }

    public function update(AuthUser $authUser, CostBuildup $costBuildup): bool
    {
        return $authUser->can('Update:CostBuildup');
    }

    public function delete(AuthUser $authUser, CostBuildup $costBuildup): bool
    {
        return $authUser->can('Delete:CostBuildup');
    }

    public function restore(AuthUser $authUser, CostBuildup $costBuildup): bool
    {
        return $authUser->can('Restore:CostBuildup');
    }

    public function forceDelete(AuthUser $authUser, CostBuildup $costBuildup): bool
    {
        return $authUser->can('ForceDelete:CostBuildup');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CostBuildup');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CostBuildup');
    }

    public function replicate(AuthUser $authUser, CostBuildup $costBuildup): bool
    {
        return $authUser->can('Replicate:CostBuildup');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CostBuildup');
    }

}