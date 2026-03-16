<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AssetManufacturer;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetManufacturerPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AssetManufacturer');
    }

    public function view(AuthUser $authUser, AssetManufacturer $assetManufacturer): bool
    {
        return $authUser->can('View:AssetManufacturer');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AssetManufacturer');
    }

    public function update(AuthUser $authUser, AssetManufacturer $assetManufacturer): bool
    {
        return $authUser->can('Update:AssetManufacturer');
    }

    public function delete(AuthUser $authUser, AssetManufacturer $assetManufacturer): bool
    {
        return $authUser->can('Delete:AssetManufacturer');
    }

    public function restore(AuthUser $authUser, AssetManufacturer $assetManufacturer): bool
    {
        return $authUser->can('Restore:AssetManufacturer');
    }

    public function forceDelete(AuthUser $authUser, AssetManufacturer $assetManufacturer): bool
    {
        return $authUser->can('ForceDelete:AssetManufacturer');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AssetManufacturer');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AssetManufacturer');
    }

    public function replicate(AuthUser $authUser, AssetManufacturer $assetManufacturer): bool
    {
        return $authUser->can('Replicate:AssetManufacturer');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AssetManufacturer');
    }

}