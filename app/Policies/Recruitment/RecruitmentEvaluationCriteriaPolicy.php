<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentEvaluationCriteria;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentEvaluationCriteriaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentEvaluationCriteria');
    }

    public function view(AuthUser $authUser, RecruitmentEvaluationCriteria $recruitmentEvaluationCriteria): bool
    {
        return $authUser->can('View:RecruitmentEvaluationCriteria');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentEvaluationCriteria');
    }

    public function update(AuthUser $authUser, RecruitmentEvaluationCriteria $recruitmentEvaluationCriteria): bool
    {
        return $authUser->can('Update:RecruitmentEvaluationCriteria');
    }

    public function delete(AuthUser $authUser, RecruitmentEvaluationCriteria $recruitmentEvaluationCriteria): bool
    {
        return $authUser->can('Delete:RecruitmentEvaluationCriteria');
    }

    public function restore(AuthUser $authUser, RecruitmentEvaluationCriteria $recruitmentEvaluationCriteria): bool
    {
        return $authUser->can('Restore:RecruitmentEvaluationCriteria');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentEvaluationCriteria $recruitmentEvaluationCriteria): bool
    {
        return $authUser->can('ForceDelete:RecruitmentEvaluationCriteria');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentEvaluationCriteria');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentEvaluationCriteria');
    }

    public function replicate(AuthUser $authUser, RecruitmentEvaluationCriteria $recruitmentEvaluationCriteria): bool
    {
        return $authUser->can('Replicate:RecruitmentEvaluationCriteria');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentEvaluationCriteria');
    }

}