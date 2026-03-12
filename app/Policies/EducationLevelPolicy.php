<?php


namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EducationLevel;
use Illuminate\Auth\Access\HandlesAuthorization;

class EducationLevelPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EducationLevel');
    }

    public function view(AuthUser $authUser, EducationLevel $record): bool
    {
        return $authUser->can('View:EducationLevel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EducationLevel');
    }

    public function update(AuthUser $authUser, EducationLevel $record): bool
    {
        return $authUser->can('Update:EducationLevel');
    }

    public function delete(AuthUser $authUser, EducationLevel $record): bool
    {
        return $authUser->can('Delete:EducationLevel');
    }

    public function restore(AuthUser $authUser, EducationLevel $record): bool
    {
        return $authUser->can('Restore:EducationLevel');
    }

    public function forceDelete(AuthUser $authUser, EducationLevel $record): bool
    {
        return $authUser->can('ForceDelete:EducationLevel');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EducationLevel');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EducationLevel');
    }

    public function replicate(AuthUser $authUser, EducationLevel $record): bool
    {
        return $authUser->can('Replicate:EducationLevel');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EducationLevel');
    }
}

