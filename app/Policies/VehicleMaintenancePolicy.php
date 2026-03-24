<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\VehicleMaintenance;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehicleMaintenancePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:VehicleMaintenance');
    }

    public function view(AuthUser $authUser, VehicleMaintenance $vehicleMaintenance): bool
    {
        return $authUser->can('View:VehicleMaintenance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:VehicleMaintenance');
    }

    public function update(AuthUser $authUser, VehicleMaintenance $vehicleMaintenance): bool
    {
        return $authUser->can('Update:VehicleMaintenance');
    }

    public function delete(AuthUser $authUser, VehicleMaintenance $vehicleMaintenance): bool
    {
        return $authUser->can('Delete:VehicleMaintenance');
    }

    public function restore(AuthUser $authUser, VehicleMaintenance $vehicleMaintenance): bool
    {
        return $authUser->can('Restore:VehicleMaintenance');
    }

    public function forceDelete(AuthUser $authUser, VehicleMaintenance $vehicleMaintenance): bool
    {
        return $authUser->can('ForceDelete:VehicleMaintenance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VehicleMaintenance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VehicleMaintenance');
    }

    public function replicate(AuthUser $authUser, VehicleMaintenance $vehicleMaintenance): bool
    {
        return $authUser->can('Replicate:VehicleMaintenance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VehicleMaintenance');
    }

}