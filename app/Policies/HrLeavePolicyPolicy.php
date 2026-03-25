<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HrLeavePolicy;
use Illuminate\Auth\Access\HandlesAuthorization;

class HrLeavePolicyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HrLeavePolicy');
    }

    public function view(AuthUser $authUser, HrLeavePolicy $hrLeavePolicy): bool
    {
        return $authUser->can('View:HrLeavePolicy');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HrLeavePolicy');
    }

    public function update(AuthUser $authUser, HrLeavePolicy $hrLeavePolicy): bool
    {
        return $authUser->can('Update:HrLeavePolicy');
    }

    public function delete(AuthUser $authUser, HrLeavePolicy $hrLeavePolicy): bool
    {
        return $authUser->can('Delete:HrLeavePolicy');
    }

    public function restore(AuthUser $authUser, HrLeavePolicy $hrLeavePolicy): bool
    {
        return $authUser->can('Restore:HrLeavePolicy');
    }

    public function forceDelete(AuthUser $authUser, HrLeavePolicy $hrLeavePolicy): bool
    {
        return $authUser->can('ForceDelete:HrLeavePolicy');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HrLeavePolicy');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HrLeavePolicy');
    }

    public function replicate(AuthUser $authUser, HrLeavePolicy $hrLeavePolicy): bool
    {
        return $authUser->can('Replicate:HrLeavePolicy');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HrLeavePolicy');
    }

}