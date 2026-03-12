<?php


namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LayoffChecklistItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class LayoffChecklistItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LayoffChecklistItem');
    }

    public function view(AuthUser $authUser, LayoffChecklistItem $record): bool
    {
        return $authUser->can('View:LayoffChecklistItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LayoffChecklistItem');
    }

    public function update(AuthUser $authUser, LayoffChecklistItem $record): bool
    {
        return $authUser->can('Update:LayoffChecklistItem');
    }

    public function delete(AuthUser $authUser, LayoffChecklistItem $record): bool
    {
        return $authUser->can('Delete:LayoffChecklistItem');
    }

    public function restore(AuthUser $authUser, LayoffChecklistItem $record): bool
    {
        return $authUser->can('Restore:LayoffChecklistItem');
    }

    public function forceDelete(AuthUser $authUser, LayoffChecklistItem $record): bool
    {
        return $authUser->can('ForceDelete:LayoffChecklistItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LayoffChecklistItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LayoffChecklistItem');
    }

    public function replicate(AuthUser $authUser, LayoffChecklistItem $record): bool
    {
        return $authUser->can('Replicate:LayoffChecklistItem');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LayoffChecklistItem');
    }
}

