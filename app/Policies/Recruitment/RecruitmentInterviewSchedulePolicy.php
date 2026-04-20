<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentInterviewSchedule;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentInterviewSchedulePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentInterviewSchedule');
    }

    public function view(AuthUser $authUser, RecruitmentInterviewSchedule $recruitmentInterviewSchedule): bool
    {
        return $authUser->can('View:RecruitmentInterviewSchedule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentInterviewSchedule');
    }

    public function update(AuthUser $authUser, RecruitmentInterviewSchedule $recruitmentInterviewSchedule): bool
    {
        return $authUser->can('Update:RecruitmentInterviewSchedule');
    }

    public function delete(AuthUser $authUser, RecruitmentInterviewSchedule $recruitmentInterviewSchedule): bool
    {
        return $authUser->can('Delete:RecruitmentInterviewSchedule');
    }

    public function restore(AuthUser $authUser, RecruitmentInterviewSchedule $recruitmentInterviewSchedule): bool
    {
        return $authUser->can('Restore:RecruitmentInterviewSchedule');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentInterviewSchedule $recruitmentInterviewSchedule): bool
    {
        return $authUser->can('ForceDelete:RecruitmentInterviewSchedule');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentInterviewSchedule');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentInterviewSchedule');
    }

    public function replicate(AuthUser $authUser, RecruitmentInterviewSchedule $recruitmentInterviewSchedule): bool
    {
        return $authUser->can('Replicate:RecruitmentInterviewSchedule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentInterviewSchedule');
    }

}