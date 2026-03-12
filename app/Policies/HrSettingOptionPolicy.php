<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HrSettingOption;
use Illuminate\Auth\Access\HandlesAuthorization;

class HrSettingOptionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HrSettingOption');
    }

    public function view(AuthUser $authUser, HrSettingOption $hrSettingOption): bool
    {
        return $authUser->can('View:HrSettingOption');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HrSettingOption');
    }

    public function update(AuthUser $authUser, HrSettingOption $hrSettingOption): bool
    {
        return $authUser->can('Update:HrSettingOption');
    }

    public function delete(AuthUser $authUser, HrSettingOption $hrSettingOption): bool
    {
        return $authUser->can('Delete:HrSettingOption');
    }

    public function restore(AuthUser $authUser, HrSettingOption $hrSettingOption): bool
    {
        return $authUser->can('Restore:HrSettingOption');
    }

    public function forceDelete(AuthUser $authUser, HrSettingOption $hrSettingOption): bool
    {
        return $authUser->can('ForceDelete:HrSettingOption');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HrSettingOption');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HrSettingOption');
    }

    public function replicate(AuthUser $authUser, HrSettingOption $hrSettingOption): bool
    {
        return $authUser->can('Replicate:HrSettingOption');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HrSettingOption');
    }

}