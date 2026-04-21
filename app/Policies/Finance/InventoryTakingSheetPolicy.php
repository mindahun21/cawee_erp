<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\InventoryTakingSheet;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryTakingSheetPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InventoryTakingSheet');
    }

    public function view(AuthUser $authUser, InventoryTakingSheet $inventoryTakingSheet): bool
    {
        return $authUser->can('View:InventoryTakingSheet');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InventoryTakingSheet');
    }

    public function update(AuthUser $authUser, InventoryTakingSheet $inventoryTakingSheet): bool
    {
        return $authUser->can('Update:InventoryTakingSheet');
    }

    public function delete(AuthUser $authUser, InventoryTakingSheet $inventoryTakingSheet): bool
    {
        return $authUser->can('Delete:InventoryTakingSheet');
    }

    public function restore(AuthUser $authUser, InventoryTakingSheet $inventoryTakingSheet): bool
    {
        return $authUser->can('Restore:InventoryTakingSheet');
    }

    public function forceDelete(AuthUser $authUser, InventoryTakingSheet $inventoryTakingSheet): bool
    {
        return $authUser->can('ForceDelete:InventoryTakingSheet');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:InventoryTakingSheet');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:InventoryTakingSheet');
    }

    public function replicate(AuthUser $authUser, InventoryTakingSheet $inventoryTakingSheet): bool
    {
        return $authUser->can('Replicate:InventoryTakingSheet');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:InventoryTakingSheet');
    }

}