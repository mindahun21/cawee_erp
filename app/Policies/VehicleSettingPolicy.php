<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\VehicleSetting;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehicleSettingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:VehicleSetting');
    }

    public function view(AuthUser $authUser, VehicleSetting $vehicleSetting): bool
    {
        return $authUser->can('View:VehicleSetting');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:VehicleSetting');
    }

    public function update(AuthUser $authUser, VehicleSetting $vehicleSetting): bool
    {
        return $authUser->can('Update:VehicleSetting');
    }

    public function delete(AuthUser $authUser, VehicleSetting $vehicleSetting): bool
    {
        return $authUser->can('Delete:VehicleSetting');
    }

    public function restore(AuthUser $authUser, VehicleSetting $vehicleSetting): bool
    {
        return $authUser->can('Restore:VehicleSetting');
    }

    public function forceDelete(AuthUser $authUser, VehicleSetting $vehicleSetting): bool
    {
        return $authUser->can('ForceDelete:VehicleSetting');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VehicleSetting');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VehicleSetting');
    }

    public function replicate(AuthUser $authUser, VehicleSetting $vehicleSetting): bool
    {
        return $authUser->can('Replicate:VehicleSetting');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VehicleSetting');
    }

}