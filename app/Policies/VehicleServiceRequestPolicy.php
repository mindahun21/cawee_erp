<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\VehicleServiceRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehicleServiceRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:VehicleServiceRequest');
    }

    public function view(AuthUser $authUser, VehicleServiceRequest $vehicleServiceRequest): bool
    {
        return $authUser->can('View:VehicleServiceRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:VehicleServiceRequest');
    }

    public function update(AuthUser $authUser, VehicleServiceRequest $vehicleServiceRequest): bool
    {
        return $authUser->can('Update:VehicleServiceRequest');
    }

    public function delete(AuthUser $authUser, VehicleServiceRequest $vehicleServiceRequest): bool
    {
        return $authUser->can('Delete:VehicleServiceRequest');
    }

    public function restore(AuthUser $authUser, VehicleServiceRequest $vehicleServiceRequest): bool
    {
        return $authUser->can('Restore:VehicleServiceRequest');
    }

    public function forceDelete(AuthUser $authUser, VehicleServiceRequest $vehicleServiceRequest): bool
    {
        return $authUser->can('ForceDelete:VehicleServiceRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VehicleServiceRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VehicleServiceRequest');
    }

    public function replicate(AuthUser $authUser, VehicleServiceRequest $vehicleServiceRequest): bool
    {
        return $authUser->can('Replicate:VehicleServiceRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VehicleServiceRequest');
    }

}