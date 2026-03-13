<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AssetType;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AssetType');
    }

    public function view(AuthUser $authUser, AssetType $assetType): bool
    {
        return $authUser->can('View:AssetType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AssetType');
    }

    public function update(AuthUser $authUser, AssetType $assetType): bool
    {
        return $authUser->can('Update:AssetType');
    }

    public function delete(AuthUser $authUser, AssetType $assetType): bool
    {
        return $authUser->can('Delete:AssetType');
    }

    public function restore(AuthUser $authUser, AssetType $assetType): bool
    {
        return $authUser->can('Restore:AssetType');
    }

    public function forceDelete(AuthUser $authUser, AssetType $assetType): bool
    {
        return $authUser->can('ForceDelete:AssetType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AssetType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AssetType');
    }

    public function replicate(AuthUser $authUser, AssetType $assetType): bool
    {
        return $authUser->can('Replicate:AssetType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AssetType');
    }

}