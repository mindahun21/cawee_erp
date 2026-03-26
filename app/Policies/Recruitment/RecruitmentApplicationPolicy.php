<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentApplication;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentApplicationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentApplication');
    }

    public function view(AuthUser $authUser, RecruitmentApplication $recruitmentApplication): bool
    {
        return $authUser->can('View:RecruitmentApplication');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentApplication');
    }

    public function update(AuthUser $authUser, RecruitmentApplication $recruitmentApplication): bool
    {
        return $authUser->can('Update:RecruitmentApplication');
    }

    public function delete(AuthUser $authUser, RecruitmentApplication $recruitmentApplication): bool
    {
        return $authUser->can('Delete:RecruitmentApplication');
    }

    public function restore(AuthUser $authUser, RecruitmentApplication $recruitmentApplication): bool
    {
        return $authUser->can('Restore:RecruitmentApplication');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentApplication $recruitmentApplication): bool
    {
        return $authUser->can('ForceDelete:RecruitmentApplication');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentApplication');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentApplication');
    }

    public function replicate(AuthUser $authUser, RecruitmentApplication $recruitmentApplication): bool
    {
        return $authUser->can('Replicate:RecruitmentApplication');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentApplication');
    }

}
