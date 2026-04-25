<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PlanningKpi;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanningKpiPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PlanningKpi');
    }

    public function view(AuthUser $authUser, PlanningKpi $planningKpi): bool
    {
        return $authUser->can('View:PlanningKpi');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PlanningKpi');
    }

    public function update(AuthUser $authUser, PlanningKpi $planningKpi): bool
    {
        return $authUser->can('Update:PlanningKpi');
    }

    public function delete(AuthUser $authUser, PlanningKpi $planningKpi): bool
    {
        return $authUser->can('Delete:PlanningKpi');
    }

    public function restore(AuthUser $authUser, PlanningKpi $planningKpi): bool
    {
        return $authUser->can('Restore:PlanningKpi');
    }

    public function forceDelete(AuthUser $authUser, PlanningKpi $planningKpi): bool
    {
        return $authUser->can('ForceDelete:PlanningKpi');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PlanningKpi');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PlanningKpi');
    }

    public function replicate(AuthUser $authUser, PlanningKpi $planningKpi): bool
    {
        return $authUser->can('Replicate:PlanningKpi');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PlanningKpi');
    }

}