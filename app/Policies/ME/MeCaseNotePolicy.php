<?php

declare(strict_types=1);

namespace App\Policies\ME;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ME\MeCaseNote;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeCaseNotePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MeCaseNote');
    }

    public function view(AuthUser $authUser, MeCaseNote $meCaseNote): bool
    {
        return $authUser->can('View:MeCaseNote');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MeCaseNote');
    }

    public function update(AuthUser $authUser, MeCaseNote $meCaseNote): bool
    {
        return $authUser->can('Update:MeCaseNote');
    }

    public function delete(AuthUser $authUser, MeCaseNote $meCaseNote): bool
    {
        return $authUser->can('Delete:MeCaseNote');
    }

    public function restore(AuthUser $authUser, MeCaseNote $meCaseNote): bool
    {
        return $authUser->can('Restore:MeCaseNote');
    }

    public function forceDelete(AuthUser $authUser, MeCaseNote $meCaseNote): bool
    {
        return $authUser->can('ForceDelete:MeCaseNote');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MeCaseNote');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MeCaseNote');
    }

    public function replicate(AuthUser $authUser, MeCaseNote $meCaseNote): bool
    {
        return $authUser->can('Replicate:MeCaseNote');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MeCaseNote');
    }

}