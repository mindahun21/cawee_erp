<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Donor;
use Illuminate\Auth\Access\HandlesAuthorization;

class DonorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Donor');
    }

    public function view(AuthUser $authUser, Donor $donor): bool
    {
        return $authUser->can('View:Donor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Donor');
    }

    public function update(AuthUser $authUser, Donor $donor): bool
    {
        return $authUser->can('Update:Donor');
    }

    public function delete(AuthUser $authUser, Donor $donor): bool
    {
        return $authUser->can('Delete:Donor');
    }

    public function restore(AuthUser $authUser, Donor $donor): bool
    {
        return $authUser->can('Restore:Donor');
    }

    public function forceDelete(AuthUser $authUser, Donor $donor): bool
    {
        return $authUser->can('ForceDelete:Donor');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Donor');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Donor');
    }

    public function replicate(AuthUser $authUser, Donor $donor): bool
    {
        return $authUser->can('Replicate:Donor');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Donor');
    }

}