<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FileSharingSetting;
use Illuminate\Auth\Access\HandlesAuthorization;

class FileSharingSettingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FileSharingSetting');
    }

    public function view(AuthUser $authUser, FileSharingSetting $fileSharingSetting): bool
    {
        return $authUser->can('View:FileSharingSetting');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FileSharingSetting');
    }

    public function update(AuthUser $authUser, FileSharingSetting $fileSharingSetting): bool
    {
        return $authUser->can('Update:FileSharingSetting');
    }

    public function delete(AuthUser $authUser, FileSharingSetting $fileSharingSetting): bool
    {
        return $authUser->can('Delete:FileSharingSetting');
    }

    public function restore(AuthUser $authUser, FileSharingSetting $fileSharingSetting): bool
    {
        return $authUser->can('Restore:FileSharingSetting');
    }

    public function forceDelete(AuthUser $authUser, FileSharingSetting $fileSharingSetting): bool
    {
        return $authUser->can('ForceDelete:FileSharingSetting');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FileSharingSetting');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FileSharingSetting');
    }

    public function replicate(AuthUser $authUser, FileSharingSetting $fileSharingSetting): bool
    {
        return $authUser->can('Replicate:FileSharingSetting');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FileSharingSetting');
    }

}