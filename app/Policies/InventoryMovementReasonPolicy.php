<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\InventoryMovementReason;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryMovementReasonPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InventoryMovementReason');
    }

    public function view(AuthUser $authUser, InventoryMovementReason $inventoryMovementReason): bool
    {
        return $authUser->can('View:InventoryMovementReason');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InventoryMovementReason');
    }

    public function update(AuthUser $authUser, InventoryMovementReason $inventoryMovementReason): bool
    {
        return $authUser->can('Update:InventoryMovementReason');
    }

    public function delete(AuthUser $authUser, InventoryMovementReason $inventoryMovementReason): bool
    {
        return $authUser->can('Delete:InventoryMovementReason');
    }

    public function restore(AuthUser $authUser, InventoryMovementReason $inventoryMovementReason): bool
    {
        return $authUser->can('Restore:InventoryMovementReason');
    }

    public function forceDelete(AuthUser $authUser, InventoryMovementReason $inventoryMovementReason): bool
    {
        return $authUser->can('ForceDelete:InventoryMovementReason');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:InventoryMovementReason');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:InventoryMovementReason');
    }

    public function replicate(AuthUser $authUser, InventoryMovementReason $inventoryMovementReason): bool
    {
        return $authUser->can('Replicate:InventoryMovementReason');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:InventoryMovementReason');
    }

}