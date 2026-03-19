<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\VehicleStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehicleStatusPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:VehicleStatus');
    }

    public function view(AuthUser $authUser, VehicleStatus $vehicleStatus): bool
    {
        return $authUser->can('View:VehicleStatus');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:VehicleStatus');
    }

    public function update(AuthUser $authUser, VehicleStatus $vehicleStatus): bool
    {
        return $authUser->can('Update:VehicleStatus');
    }

    public function delete(AuthUser $authUser, VehicleStatus $vehicleStatus): bool
    {
        return $authUser->can('Delete:VehicleStatus');
    }

    public function restore(AuthUser $authUser, VehicleStatus $vehicleStatus): bool
    {
        return $authUser->can('Restore:VehicleStatus');
    }

    public function forceDelete(AuthUser $authUser, VehicleStatus $vehicleStatus): bool
    {
        return $authUser->can('ForceDelete:VehicleStatus');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VehicleStatus');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VehicleStatus');
    }

    public function replicate(AuthUser $authUser, VehicleStatus $vehicleStatus): bool
    {
        return $authUser->can('Replicate:VehicleStatus');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VehicleStatus');
    }

}