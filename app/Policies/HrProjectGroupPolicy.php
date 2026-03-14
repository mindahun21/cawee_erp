<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HrProjectGroup;
use Illuminate\Auth\Access\HandlesAuthorization;

class HrProjectGroupPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HrProjectGroup');
    }

    public function view(AuthUser $authUser, HrProjectGroup $hrProjectGroup): bool
    {
        return $authUser->can('View:HrProjectGroup');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HrProjectGroup');
    }

    public function update(AuthUser $authUser, HrProjectGroup $hrProjectGroup): bool
    {
        return $authUser->can('Update:HrProjectGroup');
    }

    public function delete(AuthUser $authUser, HrProjectGroup $hrProjectGroup): bool
    {
        return $authUser->can('Delete:HrProjectGroup');
    }

    public function restore(AuthUser $authUser, HrProjectGroup $hrProjectGroup): bool
    {
        return $authUser->can('Restore:HrProjectGroup');
    }

    public function forceDelete(AuthUser $authUser, HrProjectGroup $hrProjectGroup): bool
    {
        return $authUser->can('ForceDelete:HrProjectGroup');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HrProjectGroup');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HrProjectGroup');
    }

    public function replicate(AuthUser $authUser, HrProjectGroup $hrProjectGroup): bool
    {
        return $authUser->can('Replicate:HrProjectGroup');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HrProjectGroup');
    }

}