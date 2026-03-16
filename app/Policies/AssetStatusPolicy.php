<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AssetStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetStatusPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AssetStatus');
    }

    public function view(AuthUser $authUser, AssetStatus $assetStatus): bool
    {
        return $authUser->can('View:AssetStatus');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AssetStatus');
    }

    public function update(AuthUser $authUser, AssetStatus $assetStatus): bool
    {
        return $authUser->can('Update:AssetStatus');
    }

    public function delete(AuthUser $authUser, AssetStatus $assetStatus): bool
    {
        return $authUser->can('Delete:AssetStatus');
    }

    public function restore(AuthUser $authUser, AssetStatus $assetStatus): bool
    {
        return $authUser->can('Restore:AssetStatus');
    }

    public function forceDelete(AuthUser $authUser, AssetStatus $assetStatus): bool
    {
        return $authUser->can('ForceDelete:AssetStatus');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AssetStatus');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AssetStatus');
    }

    public function replicate(AuthUser $authUser, AssetStatus $assetStatus): bool
    {
        return $authUser->can('Replicate:AssetStatus');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AssetStatus');
    }

}