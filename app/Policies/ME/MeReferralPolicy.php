<?php

declare(strict_types=1);

namespace App\Policies\ME;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ME\MeReferral;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeReferralPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MeReferral');
    }

    public function view(AuthUser $authUser, MeReferral $meReferral): bool
    {
        return $authUser->can('View:MeReferral');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MeReferral');
    }

    public function update(AuthUser $authUser, MeReferral $meReferral): bool
    {
        return $authUser->can('Update:MeReferral');
    }

    public function delete(AuthUser $authUser, MeReferral $meReferral): bool
    {
        return $authUser->can('Delete:MeReferral');
    }

    public function restore(AuthUser $authUser, MeReferral $meReferral): bool
    {
        return $authUser->can('Restore:MeReferral');
    }

    public function forceDelete(AuthUser $authUser, MeReferral $meReferral): bool
    {
        return $authUser->can('ForceDelete:MeReferral');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MeReferral');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MeReferral');
    }

    public function replicate(AuthUser $authUser, MeReferral $meReferral): bool
    {
        return $authUser->can('Replicate:MeReferral');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MeReferral');
    }

}