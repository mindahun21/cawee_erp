<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\WarehouseType;
use Illuminate\Auth\Access\HandlesAuthorization;

class WarehouseTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:WarehouseType');
    }

    public function view(AuthUser $authUser, WarehouseType $warehouseType): bool
    {
        return $authUser->can('View:WarehouseType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:WarehouseType');
    }

    public function update(AuthUser $authUser, WarehouseType $warehouseType): bool
    {
        return $authUser->can('Update:WarehouseType');
    }

    public function delete(AuthUser $authUser, WarehouseType $warehouseType): bool
    {
        return $authUser->can('Delete:WarehouseType');
    }

    public function restore(AuthUser $authUser, WarehouseType $warehouseType): bool
    {
        return $authUser->can('Restore:WarehouseType');
    }

    public function forceDelete(AuthUser $authUser, WarehouseType $warehouseType): bool
    {
        return $authUser->can('ForceDelete:WarehouseType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:WarehouseType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:WarehouseType');
    }

    public function replicate(AuthUser $authUser, WarehouseType $warehouseType): bool
    {
        return $authUser->can('Replicate:WarehouseType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:WarehouseType');
    }

}