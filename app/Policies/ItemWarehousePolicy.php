<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ItemWarehouse;
use Illuminate\Auth\Access\HandlesAuthorization;

class ItemWarehousePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ItemWarehouse');
    }

    public function view(AuthUser $authUser, ItemWarehouse $itemWarehouse): bool
    {
        return $authUser->can('View:ItemWarehouse');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ItemWarehouse');
    }

    public function update(AuthUser $authUser, ItemWarehouse $itemWarehouse): bool
    {
        return $authUser->can('Update:ItemWarehouse');
    }

    public function delete(AuthUser $authUser, ItemWarehouse $itemWarehouse): bool
    {
        return $authUser->can('Delete:ItemWarehouse');
    }

    public function restore(AuthUser $authUser, ItemWarehouse $itemWarehouse): bool
    {
        return $authUser->can('Restore:ItemWarehouse');
    }

    public function forceDelete(AuthUser $authUser, ItemWarehouse $itemWarehouse): bool
    {
        return $authUser->can('ForceDelete:ItemWarehouse');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ItemWarehouse');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ItemWarehouse');
    }

    public function replicate(AuthUser $authUser, ItemWarehouse $itemWarehouse): bool
    {
        return $authUser->can('Replicate:ItemWarehouse');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ItemWarehouse');
    }

}