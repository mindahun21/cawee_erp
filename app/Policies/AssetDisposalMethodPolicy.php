<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AssetDisposalMethod;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetDisposalMethodPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AssetDisposalMethod');
    }

    public function view(AuthUser $authUser, AssetDisposalMethod $assetDisposalMethod): bool
    {
        return $authUser->can('View:AssetDisposalMethod');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AssetDisposalMethod');
    }

    public function update(AuthUser $authUser, AssetDisposalMethod $assetDisposalMethod): bool
    {
        return $authUser->can('Update:AssetDisposalMethod');
    }

    public function delete(AuthUser $authUser, AssetDisposalMethod $assetDisposalMethod): bool
    {
        return $authUser->can('Delete:AssetDisposalMethod');
    }

    public function restore(AuthUser $authUser, AssetDisposalMethod $assetDisposalMethod): bool
    {
        return $authUser->can('Restore:AssetDisposalMethod');
    }

    public function forceDelete(AuthUser $authUser, AssetDisposalMethod $assetDisposalMethod): bool
    {
        return $authUser->can('ForceDelete:AssetDisposalMethod');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AssetDisposalMethod');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AssetDisposalMethod');
    }

    public function replicate(AuthUser $authUser, AssetDisposalMethod $assetDisposalMethod): bool
    {
        return $authUser->can('Replicate:AssetDisposalMethod');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AssetDisposalMethod');
    }

}