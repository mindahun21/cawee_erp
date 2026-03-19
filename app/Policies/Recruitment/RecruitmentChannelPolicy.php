<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use App\Models\Recruitment\Recruitment\RecruitmentChannel;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentChannelPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentChannel');
    }

    public function view(AuthUser $authUser, RecruitmentChannel $recruitmentChannel): bool
    {
        return $authUser->can('View:RecruitmentChannel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentChannel');
    }

    public function update(AuthUser $authUser, RecruitmentChannel $recruitmentChannel): bool
    {
        return $authUser->can('Update:RecruitmentChannel');
    }

    public function delete(AuthUser $authUser, RecruitmentChannel $recruitmentChannel): bool
    {
        return $authUser->can('Delete:RecruitmentChannel');
    }

    public function restore(AuthUser $authUser, RecruitmentChannel $recruitmentChannel): bool
    {
        return $authUser->can('Restore:RecruitmentChannel');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentChannel $recruitmentChannel): bool
    {
        return $authUser->can('ForceDelete:RecruitmentChannel');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentChannel');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentChannel');
    }

    public function replicate(AuthUser $authUser, RecruitmentChannel $recruitmentChannel): bool
    {
        return $authUser->can('Replicate:RecruitmentChannel');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentChannel');
    }
}
