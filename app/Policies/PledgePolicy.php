<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Pledge;
use Illuminate\Auth\Access\HandlesAuthorization;

class PledgePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return true;
    }

    public function view(AuthUser $authUser, Pledge $pledge): bool
    {
        return $authUser->can('View:Pledge');
    }

    public function create(AuthUser $authUser): bool
    {
        return true; // Temporarily bypass permission check for diagnosis
    }

    public function update(AuthUser $authUser, Pledge $pledge): bool
    {
        return $authUser->can('Update:Pledge');
    }

    public function delete(AuthUser $authUser, Pledge $pledge): bool
    {
        return $authUser->can('Delete:Pledge');
    }

    public function restore(AuthUser $authUser, Pledge $pledge): bool
    {
        return $authUser->can('Restore:Pledge');
    }

    public function forceDelete(AuthUser $authUser, Pledge $pledge): bool
    {
        return $authUser->can('ForceDelete:Pledge');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Pledge');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Pledge');
    }

    public function replicate(AuthUser $authUser, Pledge $pledge): bool
    {
        return $authUser->can('Replicate:Pledge');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Pledge');
    }

}