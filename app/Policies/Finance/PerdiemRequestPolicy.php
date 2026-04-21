<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\PerdiemRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class PerdiemRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PerdiemRequest');
    }

    public function view(AuthUser $authUser, PerdiemRequest $perdiemRequest): bool
    {
        return $authUser->can('View:PerdiemRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PerdiemRequest');
    }

    public function update(AuthUser $authUser, PerdiemRequest $perdiemRequest): bool
    {
        return $authUser->can('Update:PerdiemRequest');
    }

    public function delete(AuthUser $authUser, PerdiemRequest $perdiemRequest): bool
    {
        return $authUser->can('Delete:PerdiemRequest');
    }

    public function restore(AuthUser $authUser, PerdiemRequest $perdiemRequest): bool
    {
        return $authUser->can('Restore:PerdiemRequest');
    }

    public function forceDelete(AuthUser $authUser, PerdiemRequest $perdiemRequest): bool
    {
        return $authUser->can('ForceDelete:PerdiemRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PerdiemRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PerdiemRequest');
    }

    public function replicate(AuthUser $authUser, PerdiemRequest $perdiemRequest): bool
    {
        return $authUser->can('Replicate:PerdiemRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PerdiemRequest');
    }

}