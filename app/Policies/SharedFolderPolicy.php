<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SharedFolder;
use Illuminate\Auth\Access\HandlesAuthorization;

class SharedFolderPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SharedFolder');
    }

    public function view(AuthUser $authUser, SharedFolder $sharedFolder): bool
    {
        return $authUser->can('View:SharedFolder');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SharedFolder');
    }

    public function update(AuthUser $authUser, SharedFolder $sharedFolder): bool
    {
        return $authUser->can('Update:SharedFolder');
    }

    public function delete(AuthUser $authUser, SharedFolder $sharedFolder): bool
    {
        return $authUser->can('Delete:SharedFolder');
    }

    public function restore(AuthUser $authUser, SharedFolder $sharedFolder): bool
    {
        return $authUser->can('Restore:SharedFolder');
    }

    public function forceDelete(AuthUser $authUser, SharedFolder $sharedFolder): bool
    {
        return $authUser->can('ForceDelete:SharedFolder');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SharedFolder');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SharedFolder');
    }

    public function replicate(AuthUser $authUser, SharedFolder $sharedFolder): bool
    {
        return $authUser->can('Replicate:SharedFolder');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SharedFolder');
    }

}