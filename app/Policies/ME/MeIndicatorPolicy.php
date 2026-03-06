<?php

declare(strict_types=1);

namespace App\Policies\ME;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ME\MeIndicator;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeIndicatorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MeIndicator');
    }

    public function view(AuthUser $authUser, MeIndicator $meIndicator): bool
    {
        return $authUser->can('View:MeIndicator');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MeIndicator');
    }

    public function update(AuthUser $authUser, MeIndicator $meIndicator): bool
    {
        return $authUser->can('Update:MeIndicator');
    }

    public function delete(AuthUser $authUser, MeIndicator $meIndicator): bool
    {
        return $authUser->can('Delete:MeIndicator');
    }

    public function restore(AuthUser $authUser, MeIndicator $meIndicator): bool
    {
        return $authUser->can('Restore:MeIndicator');
    }

    public function forceDelete(AuthUser $authUser, MeIndicator $meIndicator): bool
    {
        return $authUser->can('ForceDelete:MeIndicator');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MeIndicator');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MeIndicator');
    }

    public function replicate(AuthUser $authUser, MeIndicator $meIndicator): bool
    {
        return $authUser->can('Replicate:MeIndicator');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MeIndicator');
    }

}