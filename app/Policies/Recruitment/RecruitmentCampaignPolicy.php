<?php

declare(strict_types=1);

namespace App\Policies\Recruitment;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Recruitment\RecruitmentCampaign;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentCampaignPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RecruitmentCampaign');
    }

    public function view(AuthUser $authUser, RecruitmentCampaign $recruitmentCampaign): bool
    {
        return $authUser->can('View:RecruitmentCampaign');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RecruitmentCampaign');
    }

    public function update(AuthUser $authUser, RecruitmentCampaign $recruitmentCampaign): bool
    {
        return $authUser->can('Update:RecruitmentCampaign');
    }

    public function delete(AuthUser $authUser, RecruitmentCampaign $recruitmentCampaign): bool
    {
        return $authUser->can('Delete:RecruitmentCampaign');
    }

    public function restore(AuthUser $authUser, RecruitmentCampaign $recruitmentCampaign): bool
    {
        return $authUser->can('Restore:RecruitmentCampaign');
    }

    public function forceDelete(AuthUser $authUser, RecruitmentCampaign $recruitmentCampaign): bool
    {
        return $authUser->can('ForceDelete:RecruitmentCampaign');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RecruitmentCampaign');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RecruitmentCampaign');
    }

    public function replicate(AuthUser $authUser, RecruitmentCampaign $recruitmentCampaign): bool
    {
        return $authUser->can('Replicate:RecruitmentCampaign');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RecruitmentCampaign');
    }

}