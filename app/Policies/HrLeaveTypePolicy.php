<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HrLeaveType;
use Illuminate\Auth\Access\HandlesAuthorization;

class HrLeaveTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HrLeaveType');
    }

    public function view(AuthUser $authUser, HrLeaveType $hrLeaveType): bool
    {
        return $authUser->can('View:HrLeaveType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HrLeaveType');
    }

    public function update(AuthUser $authUser, HrLeaveType $hrLeaveType): bool
    {
        return $authUser->can('Update:HrLeaveType');
    }

    public function delete(AuthUser $authUser, HrLeaveType $hrLeaveType): bool
    {
        return $authUser->can('Delete:HrLeaveType');
    }

    public function restore(AuthUser $authUser, HrLeaveType $hrLeaveType): bool
    {
        return $authUser->can('Restore:HrLeaveType');
    }

    public function forceDelete(AuthUser $authUser, HrLeaveType $hrLeaveType): bool
    {
        return $authUser->can('ForceDelete:HrLeaveType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HrLeaveType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HrLeaveType');
    }

    public function replicate(AuthUser $authUser, HrLeaveType $hrLeaveType): bool
    {
        return $authUser->can('Replicate:HrLeaveType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HrLeaveType');
    }

}