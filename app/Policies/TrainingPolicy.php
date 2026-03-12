<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Training;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Training');
    }

    public function view(AuthUser $authUser, Training $training): bool
    {
        return $authUser->can('View:Training');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Training');
    }

    public function update(AuthUser $authUser, Training $training): bool
    {
        return $authUser->can('Update:Training');
    }

    public function delete(AuthUser $authUser, Training $training): bool
    {
        return $authUser->can('Delete:Training');
    }

    public function restore(AuthUser $authUser, Training $training): bool
    {
        return $authUser->can('Restore:Training');
    }

    public function forceDelete(AuthUser $authUser, Training $training): bool
    {
        return $authUser->can('ForceDelete:Training');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Training');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Training');
    }

    public function replicate(AuthUser $authUser, Training $training): bool
    {
        return $authUser->can('Replicate:Training');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Training');
    }

}