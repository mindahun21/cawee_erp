<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RecruitmentSkill;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentSkillPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentSkill');
    }

    public function view(AuthUser $authUser, RecruitmentSkill $recruitmentSkill): bool
    {
        return $authUser->can('View:RecruitmentSkill');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentSkill');
    }

    public function update(AuthUser $authUser, RecruitmentSkill $recruitmentSkill): bool
    {
        return $authUser->can('Update:RecruitmentSkill');
    }

    public function delete(AuthUser $authUser, RecruitmentSkill $recruitmentSkill): bool
    {
        return $authUser->can('Delete:RecruitmentSkill');
    }

    public function restore(AuthUser $authUser, RecruitmentSkill $recruitmentSkill): bool
    {
        return $authUser->can('Restore:RecruitmentSkill');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentSkill $recruitmentSkill): bool
    {
        return $authUser->can('ForceDelete:RecruitmentSkill');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentSkill');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentSkill');
    }

    public function replicate(AuthUser $authUser, RecruitmentSkill $recruitmentSkill): bool
    {
        return $authUser->can('Replicate:RecruitmentSkill');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentSkill');
    }

}