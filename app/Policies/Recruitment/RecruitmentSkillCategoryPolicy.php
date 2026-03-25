<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentSkillCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentSkillCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentSkillCategory');
    }

    public function view(AuthUser $authUser, RecruitmentSkillCategory $recruitmentSkillCategory): bool
    {
        return $authUser->can('View:RecruitmentSkillCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentSkillCategory');
    }

    public function update(AuthUser $authUser, RecruitmentSkillCategory $recruitmentSkillCategory): bool
    {
        return $authUser->can('Update:RecruitmentSkillCategory');
    }

    public function delete(AuthUser $authUser, RecruitmentSkillCategory $recruitmentSkillCategory): bool
    {
        return $authUser->can('Delete:RecruitmentSkillCategory');
    }

    public function restore(AuthUser $authUser, RecruitmentSkillCategory $recruitmentSkillCategory): bool
    {
        return $authUser->can('Restore:RecruitmentSkillCategory');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentSkillCategory $recruitmentSkillCategory): bool
    {
        return $authUser->can('ForceDelete:RecruitmentSkillCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentSkillCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentSkillCategory');
    }

    public function replicate(AuthUser $authUser, RecruitmentSkillCategory $recruitmentSkillCategory): bool
    {
        return $authUser->can('Replicate:RecruitmentSkillCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentSkillCategory');
    }

}