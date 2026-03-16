<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RecruitmentCompany;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentCompanyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentCompany');
    }

    public function view(AuthUser $authUser, RecruitmentCompany $recruitmentCompany): bool
    {
        return $authUser->can('View:RecruitmentCompany');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentCompany');
    }

    public function update(AuthUser $authUser, RecruitmentCompany $recruitmentCompany): bool
    {
        return $authUser->can('Update:RecruitmentCompany');
    }

    public function delete(AuthUser $authUser, RecruitmentCompany $recruitmentCompany): bool
    {
        return $authUser->can('Delete:RecruitmentCompany');
    }

    public function restore(AuthUser $authUser, RecruitmentCompany $recruitmentCompany): bool
    {
        return $authUser->can('Restore:RecruitmentCompany');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentCompany $recruitmentCompany): bool
    {
        return $authUser->can('ForceDelete:RecruitmentCompany');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentCompany');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentCompany');
    }

    public function replicate(AuthUser $authUser, RecruitmentCompany $recruitmentCompany): bool
    {
        return $authUser->can('Replicate:RecruitmentCompany');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentCompany');
    }

}