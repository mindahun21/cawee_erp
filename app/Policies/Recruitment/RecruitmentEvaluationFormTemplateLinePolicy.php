<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentEvaluationFormTemplateLine;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentEvaluationFormTemplateLinePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentEvaluationFormTemplateLine');
    }

    public function view(AuthUser $authUser, RecruitmentEvaluationFormTemplateLine $line): bool
    {
        return $authUser->can('View:RecruitmentEvaluationFormTemplateLine');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentEvaluationFormTemplateLine');
    }

    public function update(AuthUser $authUser, RecruitmentEvaluationFormTemplateLine $line): bool
    {
        return $authUser->can('Update:RecruitmentEvaluationFormTemplateLine');
    }

    public function delete(AuthUser $authUser, RecruitmentEvaluationFormTemplateLine $line): bool
    {
        return $authUser->can('Delete:RecruitmentEvaluationFormTemplateLine');
    }
}
