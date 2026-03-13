<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AcquisitionType;
use Illuminate\Auth\Access\HandlesAuthorization;

class AcquisitionTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AcquisitionType');
    }

    public function view(AuthUser $authUser, AcquisitionType $acquisitionType): bool
    {
        return $authUser->can('View:AcquisitionType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AcquisitionType');
    }

    public function update(AuthUser $authUser, AcquisitionType $acquisitionType): bool
    {
        return $authUser->can('Update:AcquisitionType');
    }

    public function delete(AuthUser $authUser, AcquisitionType $acquisitionType): bool
    {
        return $authUser->can('Delete:AcquisitionType');
    }

    public function restore(AuthUser $authUser, AcquisitionType $acquisitionType): bool
    {
        return $authUser->can('Restore:AcquisitionType');
    }

    public function forceDelete(AuthUser $authUser, AcquisitionType $acquisitionType): bool
    {
        return $authUser->can('ForceDelete:AcquisitionType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AcquisitionType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AcquisitionType');
    }

    public function replicate(AuthUser $authUser, AcquisitionType $acquisitionType): bool
    {
        return $authUser->can('Replicate:AcquisitionType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AcquisitionType');
    }

}