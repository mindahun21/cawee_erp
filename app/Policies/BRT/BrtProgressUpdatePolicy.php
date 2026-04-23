<?php

declare(strict_types=1);

namespace App\Policies\BRT;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BRT\BrtProgressUpdate;
use Illuminate\Auth\Access\HandlesAuthorization;

class BrtProgressUpdatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BrtProgressUpdate');
    }

    public function view(AuthUser $authUser, BrtProgressUpdate $brtProgressUpdate): bool
    {
        return $authUser->can('View:BrtProgressUpdate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BrtProgressUpdate');
    }

    public function update(AuthUser $authUser, BrtProgressUpdate $brtProgressUpdate): bool
    {
        return $authUser->can('Update:BrtProgressUpdate');
    }

    public function delete(AuthUser $authUser, BrtProgressUpdate $brtProgressUpdate): bool
    {
        return $authUser->can('Delete:BrtProgressUpdate');
    }

    public function restore(AuthUser $authUser, BrtProgressUpdate $brtProgressUpdate): bool
    {
        return $authUser->can('Restore:BrtProgressUpdate');
    }

    public function forceDelete(AuthUser $authUser, BrtProgressUpdate $brtProgressUpdate): bool
    {
        return $authUser->can('ForceDelete:BrtProgressUpdate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BrtProgressUpdate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BrtProgressUpdate');
    }

    public function replicate(AuthUser $authUser, BrtProgressUpdate $brtProgressUpdate): bool
    {
        return $authUser->can('Replicate:BrtProgressUpdate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BrtProgressUpdate');
    }

}