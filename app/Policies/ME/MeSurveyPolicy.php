<?php

declare(strict_types=1);

namespace App\Policies\ME;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ME\MeSurvey;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeSurveyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MeSurvey');
    }

    public function view(AuthUser $authUser, MeSurvey $meSurvey): bool
    {
        return $authUser->can('View:MeSurvey');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MeSurvey');
    }

    public function update(AuthUser $authUser, MeSurvey $meSurvey): bool
    {
        return $authUser->can('Update:MeSurvey');
    }

    public function delete(AuthUser $authUser, MeSurvey $meSurvey): bool
    {
        return $authUser->can('Delete:MeSurvey');
    }

    public function restore(AuthUser $authUser, MeSurvey $meSurvey): bool
    {
        return $authUser->can('Restore:MeSurvey');
    }

    public function forceDelete(AuthUser $authUser, MeSurvey $meSurvey): bool
    {
        return $authUser->can('ForceDelete:MeSurvey');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MeSurvey');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MeSurvey');
    }

    public function replicate(AuthUser $authUser, MeSurvey $meSurvey): bool
    {
        return $authUser->can('Replicate:MeSurvey');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MeSurvey');
    }

}