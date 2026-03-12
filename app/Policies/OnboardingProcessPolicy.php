<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\OnboardingProcess;
use Illuminate\Auth\Access\HandlesAuthorization;

class OnboardingProcessPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OnboardingProcess');
    }

    public function view(AuthUser $authUser, OnboardingProcess $onboardingProcess): bool
    {
        return $authUser->can('View:OnboardingProcess');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OnboardingProcess');
    }

    public function update(AuthUser $authUser, OnboardingProcess $onboardingProcess): bool
    {
        return $authUser->can('Update:OnboardingProcess');
    }

    public function delete(AuthUser $authUser, OnboardingProcess $onboardingProcess): bool
    {
        return $authUser->can('Delete:OnboardingProcess');
    }

    public function restore(AuthUser $authUser, OnboardingProcess $onboardingProcess): bool
    {
        return $authUser->can('Restore:OnboardingProcess');
    }

    public function forceDelete(AuthUser $authUser, OnboardingProcess $onboardingProcess): bool
    {
        return $authUser->can('ForceDelete:OnboardingProcess');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OnboardingProcess');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OnboardingProcess');
    }

    public function replicate(AuthUser $authUser, OnboardingProcess $onboardingProcess): bool
    {
        return $authUser->can('Replicate:OnboardingProcess');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OnboardingProcess');
    }

}