<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Landlord;
use Illuminate\Auth\Access\HandlesAuthorization;

class LandlordPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Landlord');
    }

    public function view(AuthUser $authUser, Landlord $landlord): bool
    {
        return $authUser->can('View:Landlord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Landlord');
    }

    public function update(AuthUser $authUser, Landlord $landlord): bool
    {
        return $authUser->can('Update:Landlord');
    }

    public function delete(AuthUser $authUser, Landlord $landlord): bool
    {
        return $authUser->can('Delete:Landlord');
    }

    public function restore(AuthUser $authUser, Landlord $landlord): bool
    {
        return $authUser->can('Restore:Landlord');
    }

    public function forceDelete(AuthUser $authUser, Landlord $landlord): bool
    {
        return $authUser->can('ForceDelete:Landlord');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Landlord');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Landlord');
    }

    public function replicate(AuthUser $authUser, Landlord $landlord): bool
    {
        return $authUser->can('Replicate:Landlord');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Landlord');
    }

}