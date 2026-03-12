<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Delegation;
use Illuminate\Auth\Access\HandlesAuthorization;

class DelegationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Delegation');
    }

    public function view(AuthUser $authUser, Delegation $delegation): bool
    {
        return $authUser->can('View:Delegation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Delegation');
    }

    public function update(AuthUser $authUser, Delegation $delegation): bool
    {
        return $authUser->can('Update:Delegation');
    }

    public function delete(AuthUser $authUser, Delegation $delegation): bool
    {
        return $authUser->can('Delete:Delegation');
    }

    public function restore(AuthUser $authUser, Delegation $delegation): bool
    {
        return $authUser->can('Restore:Delegation');
    }

    public function forceDelete(AuthUser $authUser, Delegation $delegation): bool
    {
        return $authUser->can('ForceDelete:Delegation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Delegation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Delegation');
    }

    public function replicate(AuthUser $authUser, Delegation $delegation): bool
    {
        return $authUser->can('Replicate:Delegation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Delegation');
    }

}