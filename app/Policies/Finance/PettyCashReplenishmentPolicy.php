<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\PettyCashReplenishment;
use Illuminate\Auth\Access\HandlesAuthorization;

class PettyCashReplenishmentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PettyCashReplenishment');
    }

    public function view(AuthUser $authUser, PettyCashReplenishment $pettyCashReplenishment): bool
    {
        return $authUser->can('View:PettyCashReplenishment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PettyCashReplenishment');
    }

    public function update(AuthUser $authUser, PettyCashReplenishment $pettyCashReplenishment): bool
    {
        return $authUser->can('Update:PettyCashReplenishment');
    }

    public function delete(AuthUser $authUser, PettyCashReplenishment $pettyCashReplenishment): bool
    {
        return $authUser->can('Delete:PettyCashReplenishment');
    }

    public function restore(AuthUser $authUser, PettyCashReplenishment $pettyCashReplenishment): bool
    {
        return $authUser->can('Restore:PettyCashReplenishment');
    }

    public function forceDelete(AuthUser $authUser, PettyCashReplenishment $pettyCashReplenishment): bool
    {
        return $authUser->can('ForceDelete:PettyCashReplenishment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PettyCashReplenishment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PettyCashReplenishment');
    }

    public function replicate(AuthUser $authUser, PettyCashReplenishment $pettyCashReplenishment): bool
    {
        return $authUser->can('Replicate:PettyCashReplenishment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PettyCashReplenishment');
    }

}