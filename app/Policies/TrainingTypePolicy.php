<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TrainingType;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TrainingType');
    }

    public function view(AuthUser $authUser, TrainingType $trainingType): bool
    {
        return $authUser->can('View:TrainingType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TrainingType');
    }

    public function update(AuthUser $authUser, TrainingType $trainingType): bool
    {
        return $authUser->can('Update:TrainingType');
    }

    public function delete(AuthUser $authUser, TrainingType $trainingType): bool
    {
        return $authUser->can('Delete:TrainingType');
    }

    public function restore(AuthUser $authUser, TrainingType $trainingType): bool
    {
        return $authUser->can('Restore:TrainingType');
    }

    public function forceDelete(AuthUser $authUser, TrainingType $trainingType): bool
    {
        return $authUser->can('ForceDelete:TrainingType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TrainingType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TrainingType');
    }

    public function replicate(AuthUser $authUser, TrainingType $trainingType): bool
    {
        return $authUser->can('Replicate:TrainingType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TrainingType');
    }

}