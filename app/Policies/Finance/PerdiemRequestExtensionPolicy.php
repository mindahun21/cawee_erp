<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\PerdiemRequestExtension;
use Illuminate\Auth\Access\HandlesAuthorization;

class PerdiemRequestExtensionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PerdiemRequestExtension');
    }

    public function view(AuthUser $authUser, PerdiemRequestExtension $perdiemRequestExtension): bool
    {
        return $authUser->can('View:PerdiemRequestExtension');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PerdiemRequestExtension');
    }

    public function update(AuthUser $authUser, PerdiemRequestExtension $perdiemRequestExtension): bool
    {
        return $authUser->can('Update:PerdiemRequestExtension');
    }

    public function delete(AuthUser $authUser, PerdiemRequestExtension $perdiemRequestExtension): bool
    {
        return $authUser->can('Delete:PerdiemRequestExtension');
    }

    public function restore(AuthUser $authUser, PerdiemRequestExtension $perdiemRequestExtension): bool
    {
        return $authUser->can('Restore:PerdiemRequestExtension');
    }

    public function forceDelete(AuthUser $authUser, PerdiemRequestExtension $perdiemRequestExtension): bool
    {
        return $authUser->can('ForceDelete:PerdiemRequestExtension');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PerdiemRequestExtension');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PerdiemRequestExtension');
    }

    public function replicate(AuthUser $authUser, PerdiemRequestExtension $perdiemRequestExtension): bool
    {
        return $authUser->can('Replicate:PerdiemRequestExtension');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PerdiemRequestExtension');
    }

}