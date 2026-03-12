<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RecruitmentInterview;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentInterviewPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentInterview');
    }

    public function view(AuthUser $authUser, RecruitmentInterview $recruitmentInterview): bool
    {
        return $authUser->can('View:RecruitmentInterview');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentInterview');
    }

    public function update(AuthUser $authUser, RecruitmentInterview $recruitmentInterview): bool
    {
        return $authUser->can('Update:RecruitmentInterview');
    }

    public function delete(AuthUser $authUser, RecruitmentInterview $recruitmentInterview): bool
    {
        return $authUser->can('Delete:RecruitmentInterview');
    }

    public function restore(AuthUser $authUser, RecruitmentInterview $recruitmentInterview): bool
    {
        return $authUser->can('Restore:RecruitmentInterview');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentInterview $recruitmentInterview): bool
    {
        return $authUser->can('ForceDelete:RecruitmentInterview');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentInterview');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentInterview');
    }

    public function replicate(AuthUser $authUser, RecruitmentInterview $recruitmentInterview): bool
    {
        return $authUser->can('Replicate:RecruitmentInterview');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentInterview');
    }

}