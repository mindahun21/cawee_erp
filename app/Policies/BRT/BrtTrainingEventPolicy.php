<?php

declare(strict_types=1);

namespace App\Policies\BRT;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BRT\BrtTrainingEvent;
use Illuminate\Auth\Access\HandlesAuthorization;

class BrtTrainingEventPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BrtTrainingEvent');
    }

    public function view(AuthUser $authUser, BrtTrainingEvent $brtTrainingEvent): bool
    {
        return $authUser->can('View:BrtTrainingEvent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BrtTrainingEvent');
    }

    public function update(AuthUser $authUser, BrtTrainingEvent $brtTrainingEvent): bool
    {
        return $authUser->can('Update:BrtTrainingEvent');
    }

    public function delete(AuthUser $authUser, BrtTrainingEvent $brtTrainingEvent): bool
    {
        return $authUser->can('Delete:BrtTrainingEvent');
    }

    public function restore(AuthUser $authUser, BrtTrainingEvent $brtTrainingEvent): bool
    {
        return $authUser->can('Restore:BrtTrainingEvent');
    }

    public function forceDelete(AuthUser $authUser, BrtTrainingEvent $brtTrainingEvent): bool
    {
        return $authUser->can('ForceDelete:BrtTrainingEvent');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BrtTrainingEvent');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BrtTrainingEvent');
    }

    public function replicate(AuthUser $authUser, BrtTrainingEvent $brtTrainingEvent): bool
    {
        return $authUser->can('Replicate:BrtTrainingEvent');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BrtTrainingEvent');
    }

}