<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\PerdiemType;
use Illuminate\Auth\Access\HandlesAuthorization;

class PerdiemTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PerdiemType');
    }

    public function view(AuthUser $authUser, PerdiemType $perdiemType): bool
    {
        return $authUser->can('View:PerdiemType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PerdiemType');
    }

    public function update(AuthUser $authUser, PerdiemType $perdiemType): bool
    {
        return $authUser->can('Update:PerdiemType');
    }

    public function delete(AuthUser $authUser, PerdiemType $perdiemType): bool
    {
        return $authUser->can('Delete:PerdiemType');
    }

    public function restore(AuthUser $authUser, PerdiemType $perdiemType): bool
    {
        return $authUser->can('Restore:PerdiemType');
    }

    public function forceDelete(AuthUser $authUser, PerdiemType $perdiemType): bool
    {
        return $authUser->can('ForceDelete:PerdiemType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PerdiemType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PerdiemType');
    }

    public function replicate(AuthUser $authUser, PerdiemType $perdiemType): bool
    {
        return $authUser->can('Replicate:PerdiemType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PerdiemType');
    }

}