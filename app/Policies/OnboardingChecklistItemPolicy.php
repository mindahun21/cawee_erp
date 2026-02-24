<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\OnboardingChecklistItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class OnboardingChecklistItemPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OnboardingChecklistItem');
    }

    public function view(AuthUser $authUser, OnboardingChecklistItem $onboardingChecklistItem): bool
    {
        return $authUser->can('View:OnboardingChecklistItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OnboardingChecklistItem');
    }

    public function update(AuthUser $authUser, OnboardingChecklistItem $onboardingChecklistItem): bool
    {
        return $authUser->can('Update:OnboardingChecklistItem');
    }

    public function delete(AuthUser $authUser, OnboardingChecklistItem $onboardingChecklistItem): bool
    {
        return $authUser->can('Delete:OnboardingChecklistItem');
    }

    public function restore(AuthUser $authUser, OnboardingChecklistItem $onboardingChecklistItem): bool
    {
        return $authUser->can('Restore:OnboardingChecklistItem');
    }

    public function forceDelete(AuthUser $authUser, OnboardingChecklistItem $onboardingChecklistItem): bool
    {
        return $authUser->can('ForceDelete:OnboardingChecklistItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OnboardingChecklistItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OnboardingChecklistItem');
    }

    public function replicate(AuthUser $authUser, OnboardingChecklistItem $onboardingChecklistItem): bool
    {
        return $authUser->can('Replicate:OnboardingChecklistItem');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OnboardingChecklistItem');
    }

}