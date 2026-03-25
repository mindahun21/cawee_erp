<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentIndustry;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentIndustryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentIndustry');
    }

    public function view(AuthUser $authUser, RecruitmentIndustry $recruitmentIndustry): bool
    {
        return $authUser->can('View:RecruitmentIndustry');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentIndustry');
    }

    public function update(AuthUser $authUser, RecruitmentIndustry $recruitmentIndustry): bool
    {
        return $authUser->can('Update:RecruitmentIndustry');
    }

    public function delete(AuthUser $authUser, RecruitmentIndustry $recruitmentIndustry): bool
    {
        return $authUser->can('Delete:RecruitmentIndustry');
    }

    public function restore(AuthUser $authUser, RecruitmentIndustry $recruitmentIndustry): bool
    {
        return $authUser->can('Restore:RecruitmentIndustry');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentIndustry $recruitmentIndustry): bool
    {
        return $authUser->can('ForceDelete:RecruitmentIndustry');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentIndustry');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentIndustry');
    }

    public function replicate(AuthUser $authUser, RecruitmentIndustry $recruitmentIndustry): bool
    {
        return $authUser->can('Replicate:RecruitmentIndustry');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentIndustry');
    }

}