<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EvaluationCriteria;
use Illuminate\Auth\Access\HandlesAuthorization;

class EvaluationCriteriaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EvaluationCriteria');
    }

    public function view(AuthUser $authUser, EvaluationCriteria $evaluationCriteria): bool
    {
        return $authUser->can('View:EvaluationCriteria');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EvaluationCriteria');
    }

    public function update(AuthUser $authUser, EvaluationCriteria $evaluationCriteria): bool
    {
        return $authUser->can('Update:EvaluationCriteria');
    }

    public function delete(AuthUser $authUser, EvaluationCriteria $evaluationCriteria): bool
    {
        return $authUser->can('Delete:EvaluationCriteria');
    }

    public function restore(AuthUser $authUser, EvaluationCriteria $evaluationCriteria): bool
    {
        return $authUser->can('Restore:EvaluationCriteria');
    }

    public function forceDelete(AuthUser $authUser, EvaluationCriteria $evaluationCriteria): bool
    {
        return $authUser->can('ForceDelete:EvaluationCriteria');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EvaluationCriteria');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EvaluationCriteria');
    }

    public function replicate(AuthUser $authUser, EvaluationCriteria $evaluationCriteria): bool
    {
        return $authUser->can('Replicate:EvaluationCriteria');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EvaluationCriteria');
    }

}