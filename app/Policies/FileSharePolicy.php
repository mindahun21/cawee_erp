<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FileShare;
use Illuminate\Auth\Access\HandlesAuthorization;

class FileSharePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FileShare');
    }

    public function view(AuthUser $authUser, FileShare $fileShare): bool
    {
        return $authUser->can('View:FileShare');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FileShare');
    }

    public function update(AuthUser $authUser, FileShare $fileShare): bool
    {
        return $authUser->can('Update:FileShare');
    }

    public function delete(AuthUser $authUser, FileShare $fileShare): bool
    {
        return $authUser->can('Delete:FileShare');
    }

    public function restore(AuthUser $authUser, FileShare $fileShare): bool
    {
        return $authUser->can('Restore:FileShare');
    }

    public function forceDelete(AuthUser $authUser, FileShare $fileShare): bool
    {
        return $authUser->can('ForceDelete:FileShare');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FileShare');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FileShare');
    }

    public function replicate(AuthUser $authUser, FileShare $fileShare): bool
    {
        return $authUser->can('Replicate:FileShare');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FileShare');
    }

}