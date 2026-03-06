<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SalaryGrade;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalaryGradePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SalaryGrade');
    }

    public function view(AuthUser $authUser, SalaryGrade $salaryGrade): bool
    {
        return $authUser->can('View:SalaryGrade');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SalaryGrade');
    }

    public function update(AuthUser $authUser, SalaryGrade $salaryGrade): bool
    {
        return $authUser->can('Update:SalaryGrade');
    }

    public function delete(AuthUser $authUser, SalaryGrade $salaryGrade): bool
    {
        return $authUser->can('Delete:SalaryGrade');
    }

    public function restore(AuthUser $authUser, SalaryGrade $salaryGrade): bool
    {
        return $authUser->can('Restore:SalaryGrade');
    }

    public function forceDelete(AuthUser $authUser, SalaryGrade $salaryGrade): bool
    {
        return $authUser->can('ForceDelete:SalaryGrade');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SalaryGrade');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SalaryGrade');
    }

    public function replicate(AuthUser $authUser, SalaryGrade $salaryGrade): bool
    {
        return $authUser->can('Replicate:SalaryGrade');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SalaryGrade');
    }

}