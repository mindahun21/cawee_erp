<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentCandidate;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentCandidatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentCandidate');
    }

    public function view(AuthUser $authUser, RecruitmentCandidate $recruitmentCandidate): bool
    {
        return $authUser->can('View:RecruitmentCandidate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentCandidate');
    }

    public function update(AuthUser $authUser, RecruitmentCandidate $recruitmentCandidate): bool
    {
        return $authUser->can('Update:RecruitmentCandidate');
    }

    public function delete(AuthUser $authUser, RecruitmentCandidate $recruitmentCandidate): bool
    {
        return $authUser->can('Delete:RecruitmentCandidate');
    }

    public function restore(AuthUser $authUser, RecruitmentCandidate $recruitmentCandidate): bool
    {
        return $authUser->can('Restore:RecruitmentCandidate');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentCandidate $recruitmentCandidate): bool
    {
        return $authUser->can('ForceDelete:RecruitmentCandidate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentCandidate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentCandidate');
    }

    public function replicate(AuthUser $authUser, RecruitmentCandidate $recruitmentCandidate): bool
    {
        return $authUser->can('Replicate:RecruitmentCandidate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentCandidate');
    }

}