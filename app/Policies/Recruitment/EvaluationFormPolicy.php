<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentEvaluationForm;
use Illuminate\Auth\Access\HandlesAuthorization;

class EvaluationFormPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentEvaluationForm');
    }

    public function view(AuthUser $authUser, RecruitmentEvaluationForm $evaluationForm): bool
    {
        return $authUser->can('View:RecruitmentEvaluationForm');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentEvaluationForm');
    }

    public function update(AuthUser $authUser, RecruitmentEvaluationForm $evaluationForm): bool
    {
        return $authUser->can('Update:RecruitmentEvaluationForm');
    }

    public function delete(AuthUser $authUser, RecruitmentEvaluationForm $evaluationForm): bool
    {
        return $authUser->can('Delete:RecruitmentEvaluationForm');
    }

    public function restore(AuthUser $authUser, RecruitmentEvaluationForm $evaluationForm): bool
    {
        return $authUser->can('Restore:RecruitmentEvaluationForm');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentEvaluationForm $evaluationForm): bool
    {
        return $authUser->can('ForceDelete:RecruitmentEvaluationForm');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentEvaluationForm');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentEvaluationForm');
    }

    public function replicate(AuthUser $authUser, RecruitmentEvaluationForm $evaluationForm): bool
    {
        return $authUser->can('Replicate:RecruitmentEvaluationForm');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentEvaluationForm');
    }
}