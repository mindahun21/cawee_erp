<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EmployeeContract;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeContractPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EmployeeContract');
    }

    public function view(AuthUser $authUser, EmployeeContract $employeeContract): bool
    {
        return $authUser->can('View:EmployeeContract');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EmployeeContract');
    }

    public function update(AuthUser $authUser, EmployeeContract $employeeContract): bool
    {
        return $authUser->can('Update:EmployeeContract');
    }

    public function delete(AuthUser $authUser, EmployeeContract $employeeContract): bool
    {
        return $authUser->can('Delete:EmployeeContract');
    }

    public function restore(AuthUser $authUser, EmployeeContract $employeeContract): bool
    {
        return $authUser->can('Restore:EmployeeContract');
    }

    public function forceDelete(AuthUser $authUser, EmployeeContract $employeeContract): bool
    {
        return $authUser->can('ForceDelete:EmployeeContract');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EmployeeContract');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EmployeeContract');
    }

    public function replicate(AuthUser $authUser, EmployeeContract $employeeContract): bool
    {
        return $authUser->can('Replicate:EmployeeContract');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EmployeeContract');
    }

}