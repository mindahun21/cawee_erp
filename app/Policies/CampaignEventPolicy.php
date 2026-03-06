<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CampaignEvent;
use Illuminate\Auth\Access\HandlesAuthorization;

class CampaignEventPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CampaignEvent');
    }

    public function view(AuthUser $authUser, CampaignEvent $campaignEvent): bool
    {
        return $authUser->can('View:CampaignEvent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CampaignEvent');
    }

    public function update(AuthUser $authUser, CampaignEvent $campaignEvent): bool
    {
        return $authUser->can('Update:CampaignEvent');
    }

    public function delete(AuthUser $authUser, CampaignEvent $campaignEvent): bool
    {
        return $authUser->can('Delete:CampaignEvent');
    }

    public function restore(AuthUser $authUser, CampaignEvent $campaignEvent): bool
    {
        return $authUser->can('Restore:CampaignEvent');
    }

    public function forceDelete(AuthUser $authUser, CampaignEvent $campaignEvent): bool
    {
        return $authUser->can('ForceDelete:CampaignEvent');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CampaignEvent');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CampaignEvent');
    }

    public function replicate(AuthUser $authUser, CampaignEvent $campaignEvent): bool
    {
        return $authUser->can('Replicate:CampaignEvent');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CampaignEvent');
    }

}