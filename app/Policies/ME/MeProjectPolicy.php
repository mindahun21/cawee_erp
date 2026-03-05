<?php

declare(strict_types=1);

namespace App\Policies\ME;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ME\MeProject;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeProjectPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MeProject');
    }

    public function view(AuthUser $authUser, MeProject $meProject): bool
    {
        return $authUser->can('View:MeProject');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MeProject');
    }

    public function update(AuthUser $authUser, MeProject $meProject): bool
    {
        return $authUser->can('Update:MeProject');
    }

    public function delete(AuthUser $authUser, MeProject $meProject): bool
    {
        return $authUser->can('Delete:MeProject');
    }

    public function restore(AuthUser $authUser, MeProject $meProject): bool
    {
        return $authUser->can('Restore:MeProject');
    }

    public function forceDelete(AuthUser $authUser, MeProject $meProject): bool
    {
        return $authUser->can('ForceDelete:MeProject');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MeProject');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MeProject');
    }

    public function replicate(AuthUser $authUser, MeProject $meProject): bool
    {
        return $authUser->can('Replicate:MeProject');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MeProject');
    }

}