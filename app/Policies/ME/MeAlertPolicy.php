<?php

declare(strict_types=1);

namespace App\Policies\ME;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ME\MeAlert;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeAlertPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MeAlert');
    }

    public function view(AuthUser $authUser, MeAlert $meAlert): bool
    {
        return $authUser->can('View:MeAlert');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MeAlert');
    }

    public function update(AuthUser $authUser, MeAlert $meAlert): bool
    {
        return $authUser->can('Update:MeAlert');
    }

    public function delete(AuthUser $authUser, MeAlert $meAlert): bool
    {
        return $authUser->can('Delete:MeAlert');
    }

    public function restore(AuthUser $authUser, MeAlert $meAlert): bool
    {
        return $authUser->can('Restore:MeAlert');
    }

    public function forceDelete(AuthUser $authUser, MeAlert $meAlert): bool
    {
        return $authUser->can('ForceDelete:MeAlert');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MeAlert');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MeAlert');
    }

    public function replicate(AuthUser $authUser, MeAlert $meAlert): bool
    {
        return $authUser->can('Replicate:MeAlert');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MeAlert');
    }

}