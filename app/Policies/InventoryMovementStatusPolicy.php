<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\InventoryMovementStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryMovementStatusPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InventoryMovementStatus');
    }

    public function view(AuthUser $authUser, InventoryMovementStatus $inventoryMovementStatus): bool
    {
        return $authUser->can('View:InventoryMovementStatus');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InventoryMovementStatus');
    }

    public function update(AuthUser $authUser, InventoryMovementStatus $inventoryMovementStatus): bool
    {
        return $authUser->can('Update:InventoryMovementStatus');
    }

    public function delete(AuthUser $authUser, InventoryMovementStatus $inventoryMovementStatus): bool
    {
        return $authUser->can('Delete:InventoryMovementStatus');
    }

    public function restore(AuthUser $authUser, InventoryMovementStatus $inventoryMovementStatus): bool
    {
        return $authUser->can('Restore:InventoryMovementStatus');
    }

    public function forceDelete(AuthUser $authUser, InventoryMovementStatus $inventoryMovementStatus): bool
    {
        return $authUser->can('ForceDelete:InventoryMovementStatus');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:InventoryMovementStatus');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:InventoryMovementStatus');
    }

    public function replicate(AuthUser $authUser, InventoryMovementStatus $inventoryMovementStatus): bool
    {
        return $authUser->can('Replicate:InventoryMovementStatus');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:InventoryMovementStatus');
    }

}