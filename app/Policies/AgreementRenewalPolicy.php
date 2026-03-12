<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AgreementRenewal;
use Illuminate\Auth\Access\HandlesAuthorization;

class AgreementRenewalPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AgreementRenewal');
    }

    public function view(AuthUser $authUser, AgreementRenewal $agreementRenewal): bool
    {
        return $authUser->can('View:AgreementRenewal');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AgreementRenewal');
    }

    public function update(AuthUser $authUser, AgreementRenewal $agreementRenewal): bool
    {
        return $authUser->can('Update:AgreementRenewal');
    }

    public function delete(AuthUser $authUser, AgreementRenewal $agreementRenewal): bool
    {
        return $authUser->can('Delete:AgreementRenewal');
    }

    public function restore(AuthUser $authUser, AgreementRenewal $agreementRenewal): bool
    {
        return $authUser->can('Restore:AgreementRenewal');
    }

    public function forceDelete(AuthUser $authUser, AgreementRenewal $agreementRenewal): bool
    {
        return $authUser->can('ForceDelete:AgreementRenewal');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AgreementRenewal');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AgreementRenewal');
    }

    public function replicate(AuthUser $authUser, AgreementRenewal $agreementRenewal): bool
    {
        return $authUser->can('Replicate:AgreementRenewal');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AgreementRenewal');
    }

}