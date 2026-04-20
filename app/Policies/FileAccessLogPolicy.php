<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FileAccessLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class FileAccessLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FileAccessLog');
    }

    public function view(AuthUser $authUser, FileAccessLog $fileAccessLog): bool
    {
        return $authUser->can('View:FileAccessLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FileAccessLog');
    }

    public function update(AuthUser $authUser, FileAccessLog $fileAccessLog): bool
    {
        return $authUser->can('Update:FileAccessLog');
    }

    public function delete(AuthUser $authUser, FileAccessLog $fileAccessLog): bool
    {
        return $authUser->can('Delete:FileAccessLog');
    }

    public function restore(AuthUser $authUser, FileAccessLog $fileAccessLog): bool
    {
        return $authUser->can('Restore:FileAccessLog');
    }

    public function forceDelete(AuthUser $authUser, FileAccessLog $fileAccessLog): bool
    {
        return $authUser->can('ForceDelete:FileAccessLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FileAccessLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FileAccessLog');
    }

    public function replicate(AuthUser $authUser, FileAccessLog $fileAccessLog): bool
    {
        return $authUser->can('Replicate:FileAccessLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FileAccessLog');
    }

}