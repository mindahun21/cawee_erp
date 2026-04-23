<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentCandidateReference;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentCandidateReferencePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentCandidateReference');
    }

    public function view(AuthUser $authUser, RecruitmentCandidateReference $reference): bool
    {
        return $authUser->can('View:RecruitmentCandidateReference');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentCandidateReference');
    }

    public function update(AuthUser $authUser, RecruitmentCandidateReference $reference): bool
    {
        return $authUser->can('Update:RecruitmentCandidateReference');
    }

    public function delete(AuthUser $authUser, RecruitmentCandidateReference $reference): bool
    {
        return $authUser->can('Delete:RecruitmentCandidateReference');
    }
}
