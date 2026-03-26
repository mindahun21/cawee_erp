<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentApprovalRecord;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentApprovalRecordPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentApprovalRecord');
    }

    public function view(AuthUser $authUser, RecruitmentApprovalRecord $record): bool
    {
        return $authUser->can('View:RecruitmentApprovalRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentApprovalRecord');
    }

    public function update(AuthUser $authUser, RecruitmentApprovalRecord $record): bool
    {
        return $authUser->can('Update:RecruitmentApprovalRecord');
    }

    public function delete(AuthUser $authUser, RecruitmentApprovalRecord $record): bool
    {
        return $authUser->can('Delete:RecruitmentApprovalRecord');
    }

    public function restore(AuthUser $authUser, RecruitmentApprovalRecord $record): bool
    {
        return $authUser->can('Restore:RecruitmentApprovalRecord');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentApprovalRecord $record): bool
    {
        return $authUser->can('ForceDelete:RecruitmentApprovalRecord');
    }
}
