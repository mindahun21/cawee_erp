<?php


namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Dependent;
use Illuminate\Auth\Access\HandlesAuthorization;

class DependentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Dependent');
    }

    public function view(AuthUser $authUser, Dependent $record): bool
    {
        return $authUser->can('View:Dependent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Dependent');
    }

    public function update(AuthUser $authUser, Dependent $record): bool
    {
        return $authUser->can('Update:Dependent');
    }

    public function delete(AuthUser $authUser, Dependent $record): bool
    {
        return $authUser->can('Delete:Dependent');
    }

    public function restore(AuthUser $authUser, Dependent $record): bool
    {
        return $authUser->can('Restore:Dependent');
    }

    public function forceDelete(AuthUser $authUser, Dependent $record): bool
    {
        return $authUser->can('ForceDelete:Dependent');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Dependent');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Dependent');
    }

    public function replicate(AuthUser $authUser, Dependent $record): bool
    {
        return $authUser->can('Replicate:Dependent');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Dependent');
    }
}

