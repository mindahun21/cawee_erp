<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EmployeeMovement;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeMovementPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EmployeeMovement');
    }

    public function view(AuthUser $authUser, EmployeeMovement $employeeMovement): bool
    {
        return $authUser->can('View:EmployeeMovement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EmployeeMovement');
    }

    public function update(AuthUser $authUser, EmployeeMovement $employeeMovement): bool
    {
        return $authUser->can('Update:EmployeeMovement');
    }

    public function delete(AuthUser $authUser, EmployeeMovement $employeeMovement): bool
    {
        return $authUser->can('Delete:EmployeeMovement');
    }

    public function restore(AuthUser $authUser, EmployeeMovement $employeeMovement): bool
    {
        return $authUser->can('Restore:EmployeeMovement');
    }

    public function forceDelete(AuthUser $authUser, EmployeeMovement $employeeMovement): bool
    {
        return $authUser->can('ForceDelete:EmployeeMovement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EmployeeMovement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EmployeeMovement');
    }

    public function replicate(AuthUser $authUser, EmployeeMovement $employeeMovement): bool
    {
        return $authUser->can('Replicate:EmployeeMovement');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EmployeeMovement');
    }

}