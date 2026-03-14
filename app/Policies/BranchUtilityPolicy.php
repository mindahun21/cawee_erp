<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BranchUtility;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchUtilityPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BranchUtility');
    }

    public function view(AuthUser $authUser, BranchUtility $branchUtility): bool
    {
        return $authUser->can('View:BranchUtility');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BranchUtility');
    }

    public function update(AuthUser $authUser, BranchUtility $branchUtility): bool
    {
        return $authUser->can('Update:BranchUtility');
    }

    public function delete(AuthUser $authUser, BranchUtility $branchUtility): bool
    {
        return $authUser->can('Delete:BranchUtility');
    }

    public function restore(AuthUser $authUser, BranchUtility $branchUtility): bool
    {
        return $authUser->can('Restore:BranchUtility');
    }

    public function forceDelete(AuthUser $authUser, BranchUtility $branchUtility): bool
    {
        return $authUser->can('ForceDelete:BranchUtility');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BranchUtility');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BranchUtility');
    }

    public function replicate(AuthUser $authUser, BranchUtility $branchUtility): bool
    {
        return $authUser->can('Replicate:BranchUtility');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BranchUtility');
    }

}