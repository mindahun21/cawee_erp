<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DonationType;
use Illuminate\Auth\Access\HandlesAuthorization;

class DonationTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DonationType');
    }

    public function view(AuthUser $authUser, DonationType $donationType): bool
    {
        return $authUser->can('View:DonationType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DonationType');
    }

    public function update(AuthUser $authUser, DonationType $donationType): bool
    {
        return $authUser->can('Update:DonationType');
    }

    public function delete(AuthUser $authUser, DonationType $donationType): bool
    {
        return $authUser->can('Delete:DonationType');
    }

    public function restore(AuthUser $authUser, DonationType $donationType): bool
    {
        return $authUser->can('Restore:DonationType');
    }

    public function forceDelete(AuthUser $authUser, DonationType $donationType): bool
    {
        return $authUser->can('ForceDelete:DonationType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DonationType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DonationType');
    }

    public function replicate(AuthUser $authUser, DonationType $donationType): bool
    {
        return $authUser->can('Replicate:DonationType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DonationType');
    }

}