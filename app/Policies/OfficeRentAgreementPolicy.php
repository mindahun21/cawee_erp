<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\OfficeRentAgreement;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfficeRentAgreementPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OfficeRentAgreement');
    }

    public function view(AuthUser $authUser, OfficeRentAgreement $officeRentAgreement): bool
    {
        return $authUser->can('View:OfficeRentAgreement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OfficeRentAgreement');
    }

    public function update(AuthUser $authUser, OfficeRentAgreement $officeRentAgreement): bool
    {
        return $authUser->can('Update:OfficeRentAgreement');
    }

    public function delete(AuthUser $authUser, OfficeRentAgreement $officeRentAgreement): bool
    {
        return $authUser->can('Delete:OfficeRentAgreement');
    }

    public function restore(AuthUser $authUser, OfficeRentAgreement $officeRentAgreement): bool
    {
        return $authUser->can('Restore:OfficeRentAgreement');
    }

    public function forceDelete(AuthUser $authUser, OfficeRentAgreement $officeRentAgreement): bool
    {
        return $authUser->can('ForceDelete:OfficeRentAgreement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OfficeRentAgreement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OfficeRentAgreement');
    }

    public function replicate(AuthUser $authUser, OfficeRentAgreement $officeRentAgreement): bool
    {
        return $authUser->can('Replicate:OfficeRentAgreement');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OfficeRentAgreement');
    }

}