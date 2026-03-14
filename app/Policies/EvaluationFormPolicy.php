<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EvaluationForm;
use Illuminate\Auth\Access\HandlesAuthorization;

class EvaluationFormPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EvaluationForm');
    }

    public function view(AuthUser $authUser, EvaluationForm $evaluationForm): bool
    {
        return $authUser->can('View:EvaluationForm');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EvaluationForm');
    }

    public function update(AuthUser $authUser, EvaluationForm $evaluationForm): bool
    {
        return $authUser->can('Update:EvaluationForm');
    }

    public function delete(AuthUser $authUser, EvaluationForm $evaluationForm): bool
    {
        return $authUser->can('Delete:EvaluationForm');
    }

    public function restore(AuthUser $authUser, EvaluationForm $evaluationForm): bool
    {
        return $authUser->can('Restore:EvaluationForm');
    }

    public function forceDelete(AuthUser $authUser, EvaluationForm $evaluationForm): bool
    {
        return $authUser->can('ForceDelete:EvaluationForm');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EvaluationForm');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EvaluationForm');
    }

    public function replicate(AuthUser $authUser, EvaluationForm $evaluationForm): bool
    {
        return $authUser->can('Replicate:EvaluationForm');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EvaluationForm');
    }

}