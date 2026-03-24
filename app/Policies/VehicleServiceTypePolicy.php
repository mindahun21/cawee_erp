<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\VehicleServiceType;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehicleServiceTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:VehicleServiceType');
    }

    public function view(AuthUser $authUser, VehicleServiceType $vehicleServiceType): bool
    {
        return $authUser->can('View:VehicleServiceType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:VehicleServiceType');
    }

    public function update(AuthUser $authUser, VehicleServiceType $vehicleServiceType): bool
    {
        return $authUser->can('Update:VehicleServiceType');
    }

    public function delete(AuthUser $authUser, VehicleServiceType $vehicleServiceType): bool
    {
        return $authUser->can('Delete:VehicleServiceType');
    }

    public function restore(AuthUser $authUser, VehicleServiceType $vehicleServiceType): bool
    {
        return $authUser->can('Restore:VehicleServiceType');
    }

    public function forceDelete(AuthUser $authUser, VehicleServiceType $vehicleServiceType): bool
    {
        return $authUser->can('ForceDelete:VehicleServiceType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VehicleServiceType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VehicleServiceType');
    }

    public function replicate(AuthUser $authUser, VehicleServiceType $vehicleServiceType): bool
    {
        return $authUser->can('Replicate:VehicleServiceType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VehicleServiceType');
    }

}