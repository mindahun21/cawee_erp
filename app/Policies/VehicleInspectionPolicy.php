<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\VehicleInspection;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehicleInspectionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:VehicleInspection');
    }

    public function view(AuthUser $authUser, VehicleInspection $vehicleInspection): bool
    {
        return $authUser->can('View:VehicleInspection');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:VehicleInspection');
    }

    public function update(AuthUser $authUser, VehicleInspection $vehicleInspection): bool
    {
        return $authUser->can('Update:VehicleInspection');
    }

    public function delete(AuthUser $authUser, VehicleInspection $vehicleInspection): bool
    {
        return $authUser->can('Delete:VehicleInspection');
    }

    public function restore(AuthUser $authUser, VehicleInspection $vehicleInspection): bool
    {
        return $authUser->can('Restore:VehicleInspection');
    }

    public function forceDelete(AuthUser $authUser, VehicleInspection $vehicleInspection): bool
    {
        return $authUser->can('ForceDelete:VehicleInspection');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VehicleInspection');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VehicleInspection');
    }

    public function replicate(AuthUser $authUser, VehicleInspection $vehicleInspection): bool
    {
        return $authUser->can('Replicate:VehicleInspection');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VehicleInspection');
    }

}