<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RecruitmentPlan;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentPlanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentPlan');
    }

    public function view(AuthUser $authUser, RecruitmentPlan $recruitmentPlan): bool
    {
        return $authUser->can('View:RecruitmentPlan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentPlan');
    }

    public function update(AuthUser $authUser, RecruitmentPlan $recruitmentPlan): bool
    {
        return $authUser->can('Update:RecruitmentPlan');
    }

    public function delete(AuthUser $authUser, RecruitmentPlan $recruitmentPlan): bool
    {
        return $authUser->can('Delete:RecruitmentPlan');
    }

    public function restore(AuthUser $authUser, RecruitmentPlan $recruitmentPlan): bool
    {
        return $authUser->can('Restore:RecruitmentPlan');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentPlan $recruitmentPlan): bool
    {
        return $authUser->can('ForceDelete:RecruitmentPlan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentPlan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentPlan');
    }

    public function replicate(AuthUser $authUser, RecruitmentPlan $recruitmentPlan): bool
    {
        return $authUser->can('Replicate:RecruitmentPlan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentPlan');
    }

}