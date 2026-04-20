<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentEvaluationFormTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentEvaluationFormTemplatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentEvaluationFormTemplate');
    }

    public function view(AuthUser $authUser, RecruitmentEvaluationFormTemplate $recruitmentEvaluationFormTemplate): bool
    {
        return $authUser->can('View:RecruitmentEvaluationFormTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentEvaluationFormTemplate');
    }

    public function update(AuthUser $authUser, RecruitmentEvaluationFormTemplate $recruitmentEvaluationFormTemplate): bool
    {
        return $authUser->can('Update:RecruitmentEvaluationFormTemplate');
    }

    public function delete(AuthUser $authUser, RecruitmentEvaluationFormTemplate $recruitmentEvaluationFormTemplate): bool
    {
        return $authUser->can('Delete:RecruitmentEvaluationFormTemplate');
    }

    public function restore(AuthUser $authUser, RecruitmentEvaluationFormTemplate $recruitmentEvaluationFormTemplate): bool
    {
        return $authUser->can('Restore:RecruitmentEvaluationFormTemplate');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentEvaluationFormTemplate $recruitmentEvaluationFormTemplate): bool
    {
        return $authUser->can('ForceDelete:RecruitmentEvaluationFormTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentEvaluationFormTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentEvaluationFormTemplate');
    }

    public function replicate(AuthUser $authUser, RecruitmentEvaluationFormTemplate $recruitmentEvaluationFormTemplate): bool
    {
        return $authUser->can('Replicate:RecruitmentEvaluationFormTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentEvaluationFormTemplate');
    }

}