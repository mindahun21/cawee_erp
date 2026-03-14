<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\VehicleMaintenanceRecord;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehicleMaintenanceRecordPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:VehicleMaintenanceRecord');
    }

    public function view(AuthUser $authUser, VehicleMaintenanceRecord $vehicleMaintenanceRecord): bool
    {
        return $authUser->can('View:VehicleMaintenanceRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:VehicleMaintenanceRecord');
    }

    public function update(AuthUser $authUser, VehicleMaintenanceRecord $vehicleMaintenanceRecord): bool
    {
        return $authUser->can('Update:VehicleMaintenanceRecord');
    }

    public function delete(AuthUser $authUser, VehicleMaintenanceRecord $vehicleMaintenanceRecord): bool
    {
        return $authUser->can('Delete:VehicleMaintenanceRecord');
    }

    public function restore(AuthUser $authUser, VehicleMaintenanceRecord $vehicleMaintenanceRecord): bool
    {
        return $authUser->can('Restore:VehicleMaintenanceRecord');
    }

    public function forceDelete(AuthUser $authUser, VehicleMaintenanceRecord $vehicleMaintenanceRecord): bool
    {
        return $authUser->can('ForceDelete:VehicleMaintenanceRecord');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VehicleMaintenanceRecord');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VehicleMaintenanceRecord');
    }

    public function replicate(AuthUser $authUser, VehicleMaintenanceRecord $vehicleMaintenanceRecord): bool
    {
        return $authUser->can('Replicate:VehicleMaintenanceRecord');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VehicleMaintenanceRecord');
    }

}