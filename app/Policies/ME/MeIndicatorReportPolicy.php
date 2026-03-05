<?php

declare(strict_types=1);

namespace App\Policies\ME;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ME\MeIndicatorReport;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeIndicatorReportPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MeIndicatorReport');
    }

    public function view(AuthUser $authUser, MeIndicatorReport $meIndicatorReport): bool
    {
        return $authUser->can('View:MeIndicatorReport');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MeIndicatorReport');
    }

    public function update(AuthUser $authUser, MeIndicatorReport $meIndicatorReport): bool
    {
        return $authUser->can('Update:MeIndicatorReport');
    }

    public function delete(AuthUser $authUser, MeIndicatorReport $meIndicatorReport): bool
    {
        return $authUser->can('Delete:MeIndicatorReport');
    }

    public function restore(AuthUser $authUser, MeIndicatorReport $meIndicatorReport): bool
    {
        return $authUser->can('Restore:MeIndicatorReport');
    }

    public function forceDelete(AuthUser $authUser, MeIndicatorReport $meIndicatorReport): bool
    {
        return $authUser->can('ForceDelete:MeIndicatorReport');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MeIndicatorReport');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MeIndicatorReport');
    }

    public function replicate(AuthUser $authUser, MeIndicatorReport $meIndicatorReport): bool
    {
        return $authUser->can('Replicate:MeIndicatorReport');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MeIndicatorReport');
    }

}