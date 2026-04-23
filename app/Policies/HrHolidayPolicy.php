<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HrHoliday;
use Illuminate\Auth\Access\HandlesAuthorization;

class HrHolidayPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HrHoliday');
    }

    public function view(AuthUser $authUser, HrHoliday $hrHoliday): bool
    {
        return $authUser->can('View:HrHoliday');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HrHoliday');
    }

    public function update(AuthUser $authUser, HrHoliday $hrHoliday): bool
    {
        return $authUser->can('Update:HrHoliday');
    }

    public function delete(AuthUser $authUser, HrHoliday $hrHoliday): bool
    {
        return $authUser->can('Delete:HrHoliday');
    }

    public function restore(AuthUser $authUser, HrHoliday $hrHoliday): bool
    {
        return $authUser->can('Restore:HrHoliday');
    }

    public function forceDelete(AuthUser $authUser, HrHoliday $hrHoliday): bool
    {
        return $authUser->can('ForceDelete:HrHoliday');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HrHoliday');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HrHoliday');
    }

    public function replicate(AuthUser $authUser, HrHoliday $hrHoliday): bool
    {
        return $authUser->can('Replicate:HrHoliday');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HrHoliday');
    }

}