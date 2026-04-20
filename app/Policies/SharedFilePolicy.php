<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SharedFile;
use Illuminate\Auth\Access\HandlesAuthorization;

class SharedFilePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SharedFile');
    }

    public function view(AuthUser $authUser, SharedFile $sharedFile): bool
    {
        return $authUser->can('View:SharedFile');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SharedFile');
    }

    public function update(AuthUser $authUser, SharedFile $sharedFile): bool
    {
        return $authUser->can('Update:SharedFile');
    }

    public function delete(AuthUser $authUser, SharedFile $sharedFile): bool
    {
        return $authUser->can('Delete:SharedFile');
    }

    public function restore(AuthUser $authUser, SharedFile $sharedFile): bool
    {
        return $authUser->can('Restore:SharedFile');
    }

    public function forceDelete(AuthUser $authUser, SharedFile $sharedFile): bool
    {
        return $authUser->can('ForceDelete:SharedFile');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SharedFile');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SharedFile');
    }

    public function replicate(AuthUser $authUser, SharedFile $sharedFile): bool
    {
        return $authUser->can('Replicate:SharedFile');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SharedFile');
    }

}