<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentApprovalWorkflow;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentApprovalWorkflowPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentApprovalWorkflow');
    }

    public function view(AuthUser $authUser, RecruitmentApprovalWorkflow $recruitmentApprovalWorkflow): bool
    {
        return $authUser->can('View:RecruitmentApprovalWorkflow');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentApprovalWorkflow');
    }

    public function update(AuthUser $authUser, RecruitmentApprovalWorkflow $recruitmentApprovalWorkflow): bool
    {
        return $authUser->can('Update:RecruitmentApprovalWorkflow');
    }

    public function delete(AuthUser $authUser, RecruitmentApprovalWorkflow $recruitmentApprovalWorkflow): bool
    {
        return $authUser->can('Delete:RecruitmentApprovalWorkflow');
    }

    public function restore(AuthUser $authUser, RecruitmentApprovalWorkflow $recruitmentApprovalWorkflow): bool
    {
        return $authUser->can('Restore:RecruitmentApprovalWorkflow');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentApprovalWorkflow $recruitmentApprovalWorkflow): bool
    {
        return $authUser->can('ForceDelete:RecruitmentApprovalWorkflow');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentApprovalWorkflow');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentApprovalWorkflow');
    }

    public function replicate(AuthUser $authUser, RecruitmentApprovalWorkflow $recruitmentApprovalWorkflow): bool
    {
        return $authUser->can('Replicate:RecruitmentApprovalWorkflow');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentApprovalWorkflow');
    }

}