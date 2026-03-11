<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\VehicleLicense;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehicleLicensePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:VehicleLicense');
    }

    public function view(AuthUser $authUser, VehicleLicense $vehicleLicense): bool
    {
        return $authUser->can('View:VehicleLicense');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:VehicleLicense');
    }

    public function update(AuthUser $authUser, VehicleLicense $vehicleLicense): bool
    {
        return $authUser->can('Update:VehicleLicense');
    }

    public function delete(AuthUser $authUser, VehicleLicense $vehicleLicense): bool
    {
        return $authUser->can('Delete:VehicleLicense');
    }

    public function restore(AuthUser $authUser, VehicleLicense $vehicleLicense): bool
    {
        return $authUser->can('Restore:VehicleLicense');
    }

    public function forceDelete(AuthUser $authUser, VehicleLicense $vehicleLicense): bool
    {
        return $authUser->can('ForceDelete:VehicleLicense');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VehicleLicense');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VehicleLicense');
    }

    public function replicate(AuthUser $authUser, VehicleLicense $vehicleLicense): bool
    {
        return $authUser->can('Replicate:VehicleLicense');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VehicleLicense');
    }

}