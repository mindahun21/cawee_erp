<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\VehicleFuelLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehicleFuelLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:VehicleFuelLog');
    }

    public function view(AuthUser $authUser, VehicleFuelLog $vehicleFuelLog): bool
    {
        return $authUser->can('View:VehicleFuelLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:VehicleFuelLog');
    }

    public function update(AuthUser $authUser, VehicleFuelLog $vehicleFuelLog): bool
    {
        return $authUser->can('Update:VehicleFuelLog');
    }

    public function delete(AuthUser $authUser, VehicleFuelLog $vehicleFuelLog): bool
    {
        return $authUser->can('Delete:VehicleFuelLog');
    }

    public function restore(AuthUser $authUser, VehicleFuelLog $vehicleFuelLog): bool
    {
        return $authUser->can('Restore:VehicleFuelLog');
    }

    public function forceDelete(AuthUser $authUser, VehicleFuelLog $vehicleFuelLog): bool
    {
        return $authUser->can('ForceDelete:VehicleFuelLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VehicleFuelLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VehicleFuelLog');
    }

    public function replicate(AuthUser $authUser, VehicleFuelLog $vehicleFuelLog): bool
    {
        return $authUser->can('Replicate:VehicleFuelLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VehicleFuelLog');
    }

}