<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MaintenanceStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenanceStatusPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MaintenanceStatus');
    }

    public function view(AuthUser $authUser, MaintenanceStatus $maintenanceStatus): bool
    {
        return $authUser->can('View:MaintenanceStatus');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MaintenanceStatus');
    }

    public function update(AuthUser $authUser, MaintenanceStatus $maintenanceStatus): bool
    {
        return $authUser->can('Update:MaintenanceStatus');
    }

    public function delete(AuthUser $authUser, MaintenanceStatus $maintenanceStatus): bool
    {
        return $authUser->can('Delete:MaintenanceStatus');
    }

    public function restore(AuthUser $authUser, MaintenanceStatus $maintenanceStatus): bool
    {
        return $authUser->can('Restore:MaintenanceStatus');
    }

    public function forceDelete(AuthUser $authUser, MaintenanceStatus $maintenanceStatus): bool
    {
        return $authUser->can('ForceDelete:MaintenanceStatus');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MaintenanceStatus');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MaintenanceStatus');
    }

    public function replicate(AuthUser $authUser, MaintenanceStatus $maintenanceStatus): bool
    {
        return $authUser->can('Replicate:MaintenanceStatus');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MaintenanceStatus');
    }

}