<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ItemType;
use Illuminate\Auth\Access\HandlesAuthorization;

class ItemTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ItemType');
    }

    public function view(AuthUser $authUser, ItemType $itemType): bool
    {
        return $authUser->can('View:ItemType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ItemType');
    }

    public function update(AuthUser $authUser, ItemType $itemType): bool
    {
        return $authUser->can('Update:ItemType');
    }

    public function delete(AuthUser $authUser, ItemType $itemType): bool
    {
        return $authUser->can('Delete:ItemType');
    }

    public function restore(AuthUser $authUser, ItemType $itemType): bool
    {
        return $authUser->can('Restore:ItemType');
    }

    public function forceDelete(AuthUser $authUser, ItemType $itemType): bool
    {
        return $authUser->can('ForceDelete:ItemType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ItemType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ItemType');
    }

    public function replicate(AuthUser $authUser, ItemType $itemType): bool
    {
        return $authUser->can('Replicate:ItemType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ItemType');
    }

}