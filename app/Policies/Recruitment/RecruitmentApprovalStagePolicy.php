<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentApprovalStage;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentApprovalStagePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentApprovalStage');
    }

    public function view(AuthUser $authUser, RecruitmentApprovalStage $stage): bool
    {
        return $authUser->can('View:RecruitmentApprovalStage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentApprovalStage');
    }

    public function update(AuthUser $authUser, RecruitmentApprovalStage $stage): bool
    {
        return $authUser->can('Update:RecruitmentApprovalStage');
    }

    public function delete(AuthUser $authUser, RecruitmentApprovalStage $stage): bool
    {
        return $authUser->can('Delete:RecruitmentApprovalStage');
    }
}
