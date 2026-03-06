<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PerDiemRate;
use Illuminate\Auth\Access\HandlesAuthorization;

class PerDiemRatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PerDiemRate');
    }

    public function view(AuthUser $authUser, PerDiemRate $perDiemRate): bool
    {
        return $authUser->can('View:PerDiemRate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PerDiemRate');
    }

    public function update(AuthUser $authUser, PerDiemRate $perDiemRate): bool
    {
        return $authUser->can('Update:PerDiemRate');
    }

    public function delete(AuthUser $authUser, PerDiemRate $perDiemRate): bool
    {
        return $authUser->can('Delete:PerDiemRate');
    }

    public function restore(AuthUser $authUser, PerDiemRate $perDiemRate): bool
    {
        return $authUser->can('Restore:PerDiemRate');
    }

    public function forceDelete(AuthUser $authUser, PerDiemRate $perDiemRate): bool
    {
        return $authUser->can('ForceDelete:PerDiemRate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PerDiemRate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PerDiemRate');
    }

    public function replicate(AuthUser $authUser, PerDiemRate $perDiemRate): bool
    {
        return $authUser->can('Replicate:PerDiemRate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PerDiemRate');
    }

}