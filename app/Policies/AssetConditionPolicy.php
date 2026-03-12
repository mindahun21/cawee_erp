<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AssetCondition;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetConditionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AssetCondition');
    }

    public function view(AuthUser $authUser, AssetCondition $assetCondition): bool
    {
        return $authUser->can('View:AssetCondition');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AssetCondition');
    }

    public function update(AuthUser $authUser, AssetCondition $assetCondition): bool
    {
        return $authUser->can('Update:AssetCondition');
    }

    public function delete(AuthUser $authUser, AssetCondition $assetCondition): bool
    {
        return $authUser->can('Delete:AssetCondition');
    }

    public function restore(AuthUser $authUser, AssetCondition $assetCondition): bool
    {
        return $authUser->can('Restore:AssetCondition');
    }

    public function forceDelete(AuthUser $authUser, AssetCondition $assetCondition): bool
    {
        return $authUser->can('ForceDelete:AssetCondition');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AssetCondition');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AssetCondition');
    }

    public function replicate(AuthUser $authUser, AssetCondition $assetCondition): bool
    {
        return $authUser->can('Replicate:AssetCondition');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AssetCondition');
    }

}