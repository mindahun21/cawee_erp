<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentPosition;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentPositionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentPosition');
    }

    public function view(AuthUser $authUser, RecruitmentPosition $recruitmentPosition): bool
    {
        return $authUser->can('View:RecruitmentPosition');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentPosition');
    }

    public function update(AuthUser $authUser, RecruitmentPosition $recruitmentPosition): bool
    {
        return $authUser->can('Update:RecruitmentPosition');
    }

    public function delete(AuthUser $authUser, RecruitmentPosition $recruitmentPosition): bool
    {
        return $authUser->can('Delete:RecruitmentPosition');
    }

    public function restore(AuthUser $authUser, RecruitmentPosition $recruitmentPosition): bool
    {
        return $authUser->can('Restore:RecruitmentPosition');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentPosition $recruitmentPosition): bool
    {
        return $authUser->can('ForceDelete:RecruitmentPosition');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentPosition');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentPosition');
    }

    public function replicate(AuthUser $authUser, RecruitmentPosition $recruitmentPosition): bool
    {
        return $authUser->can('Replicate:RecruitmentPosition');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentPosition');
    }

}