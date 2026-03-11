<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HrBranch;
use Illuminate\Auth\Access\HandlesAuthorization;

class HrBranchPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HrBranch');
    }

    public function view(AuthUser $authUser, HrBranch $hrBranch): bool
    {
        return $authUser->can('View:HrBranch');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HrBranch');
    }

    public function update(AuthUser $authUser, HrBranch $hrBranch): bool
    {
        return $authUser->can('Update:HrBranch');
    }

    public function delete(AuthUser $authUser, HrBranch $hrBranch): bool
    {
        return $authUser->can('Delete:HrBranch');
    }

    public function restore(AuthUser $authUser, HrBranch $hrBranch): bool
    {
        return $authUser->can('Restore:HrBranch');
    }

    public function forceDelete(AuthUser $authUser, HrBranch $hrBranch): bool
    {
        return $authUser->can('ForceDelete:HrBranch');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HrBranch');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HrBranch');
    }

    public function replicate(AuthUser $authUser, HrBranch $hrBranch): bool
    {
        return $authUser->can('Replicate:HrBranch');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HrBranch');
    }

}