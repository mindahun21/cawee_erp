<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HrLeaveRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class HrLeaveRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HrLeaveRequest');
    }

    public function view(AuthUser $authUser, HrLeaveRequest $hrLeaveRequest): bool
    {
        return $authUser->can('View:HrLeaveRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HrLeaveRequest');
    }

    public function update(AuthUser $authUser, HrLeaveRequest $hrLeaveRequest): bool
    {
        return $authUser->can('Update:HrLeaveRequest');
    }

    public function delete(AuthUser $authUser, HrLeaveRequest $hrLeaveRequest): bool
    {
        return $authUser->can('Delete:HrLeaveRequest');
    }

    public function restore(AuthUser $authUser, HrLeaveRequest $hrLeaveRequest): bool
    {
        return $authUser->can('Restore:HrLeaveRequest');
    }

    public function forceDelete(AuthUser $authUser, HrLeaveRequest $hrLeaveRequest): bool
    {
        return $authUser->can('ForceDelete:HrLeaveRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HrLeaveRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HrLeaveRequest');
    }

    public function replicate(AuthUser $authUser, HrLeaveRequest $hrLeaveRequest): bool
    {
        return $authUser->can('Replicate:HrLeaveRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HrLeaveRequest');
    }

}