<?php

declare(strict_types=1);

namespace App\Policies\ME;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ME\MeBeneficiaryFeedback;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeBeneficiaryFeedbackPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MeBeneficiaryFeedback');
    }

    public function view(AuthUser $authUser, MeBeneficiaryFeedback $meBeneficiaryFeedback): bool
    {
        return $authUser->can('View:MeBeneficiaryFeedback');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MeBeneficiaryFeedback');
    }

    public function update(AuthUser $authUser, MeBeneficiaryFeedback $meBeneficiaryFeedback): bool
    {
        return $authUser->can('Update:MeBeneficiaryFeedback');
    }

    public function delete(AuthUser $authUser, MeBeneficiaryFeedback $meBeneficiaryFeedback): bool
    {
        return $authUser->can('Delete:MeBeneficiaryFeedback');
    }

    public function restore(AuthUser $authUser, MeBeneficiaryFeedback $meBeneficiaryFeedback): bool
    {
        return $authUser->can('Restore:MeBeneficiaryFeedback');
    }

    public function forceDelete(AuthUser $authUser, MeBeneficiaryFeedback $meBeneficiaryFeedback): bool
    {
        return $authUser->can('ForceDelete:MeBeneficiaryFeedback');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MeBeneficiaryFeedback');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MeBeneficiaryFeedback');
    }

    public function replicate(AuthUser $authUser, MeBeneficiaryFeedback $meBeneficiaryFeedback): bool
    {
        return $authUser->can('Replicate:MeBeneficiaryFeedback');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MeBeneficiaryFeedback');
    }

}