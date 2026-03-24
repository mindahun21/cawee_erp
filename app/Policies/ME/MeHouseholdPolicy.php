<?php

declare(strict_types=1);

namespace App\Policies\ME;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ME\MeHousehold;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeHouseholdPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MeHousehold');
    }

    public function view(AuthUser $authUser, MeHousehold $meHousehold): bool
    {
        return $authUser->can('View:MeHousehold');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MeHousehold');
    }

    public function update(AuthUser $authUser, MeHousehold $meHousehold): bool
    {
        return $authUser->can('Update:MeHousehold');
    }

    public function delete(AuthUser $authUser, MeHousehold $meHousehold): bool
    {
        return $authUser->can('Delete:MeHousehold');
    }

    public function restore(AuthUser $authUser, MeHousehold $meHousehold): bool
    {
        return $authUser->can('Restore:MeHousehold');
    }

    public function forceDelete(AuthUser $authUser, MeHousehold $meHousehold): bool
    {
        return $authUser->can('ForceDelete:MeHousehold');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MeHousehold');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MeHousehold');
    }

    public function replicate(AuthUser $authUser, MeHousehold $meHousehold): bool
    {
        return $authUser->can('Replicate:MeHousehold');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MeHousehold');
    }

}