<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MaintenancePriority;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenancePriorityPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MaintenancePriority');
    }

    public function view(AuthUser $authUser, MaintenancePriority $maintenancePriority): bool
    {
        return $authUser->can('View:MaintenancePriority');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MaintenancePriority');
    }

    public function update(AuthUser $authUser, MaintenancePriority $maintenancePriority): bool
    {
        return $authUser->can('Update:MaintenancePriority');
    }

    public function delete(AuthUser $authUser, MaintenancePriority $maintenancePriority): bool
    {
        return $authUser->can('Delete:MaintenancePriority');
    }

    public function restore(AuthUser $authUser, MaintenancePriority $maintenancePriority): bool
    {
        return $authUser->can('Restore:MaintenancePriority');
    }

    public function forceDelete(AuthUser $authUser, MaintenancePriority $maintenancePriority): bool
    {
        return $authUser->can('ForceDelete:MaintenancePriority');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MaintenancePriority');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MaintenancePriority');
    }

    public function replicate(AuthUser $authUser, MaintenancePriority $maintenancePriority): bool
    {
        return $authUser->can('Replicate:MaintenancePriority');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MaintenancePriority');
    }

}