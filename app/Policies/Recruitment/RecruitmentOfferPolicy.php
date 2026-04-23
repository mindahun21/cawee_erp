<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentOffer;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentOfferPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentOffer');
    }

    public function view(AuthUser $authUser, RecruitmentOffer $recruitmentOffer): bool
    {
        return $authUser->can('View:RecruitmentOffer');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentOffer');
    }

    public function update(AuthUser $authUser, RecruitmentOffer $recruitmentOffer): bool
    {
        return $authUser->can('Update:RecruitmentOffer');
    }

    public function delete(AuthUser $authUser, RecruitmentOffer $recruitmentOffer): bool
    {
        return $authUser->can('Delete:RecruitmentOffer');
    }

    public function restore(AuthUser $authUser, RecruitmentOffer $recruitmentOffer): bool
    {
        return $authUser->can('Restore:RecruitmentOffer');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentOffer $recruitmentOffer): bool
    {
        return $authUser->can('ForceDelete:RecruitmentOffer');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentOffer');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentOffer');
    }

    public function replicate(AuthUser $authUser, RecruitmentOffer $recruitmentOffer): bool
    {
        return $authUser->can('Replicate:RecruitmentOffer');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentOffer');
    }

}