<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HrTimesheet;
use Illuminate\Auth\Access\HandlesAuthorization;

class HrTimesheetPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HrTimesheet');
    }

    public function view(AuthUser $authUser, HrTimesheet $hrTimesheet): bool
    {
        return $authUser->can('View:HrTimesheet');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HrTimesheet');
    }

    public function update(AuthUser $authUser, HrTimesheet $hrTimesheet): bool
    {
        return $authUser->can('Update:HrTimesheet');
    }

    public function delete(AuthUser $authUser, HrTimesheet $hrTimesheet): bool
    {
        return $authUser->can('Delete:HrTimesheet');
    }

    public function restore(AuthUser $authUser, HrTimesheet $hrTimesheet): bool
    {
        return $authUser->can('Restore:HrTimesheet');
    }

    public function forceDelete(AuthUser $authUser, HrTimesheet $hrTimesheet): bool
    {
        return $authUser->can('ForceDelete:HrTimesheet');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HrTimesheet');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HrTimesheet');
    }

    public function replicate(AuthUser $authUser, HrTimesheet $hrTimesheet): bool
    {
        return $authUser->can('Replicate:HrTimesheet');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HrTimesheet');
    }

}