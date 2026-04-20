<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\PettyCashFund;
use Illuminate\Auth\Access\HandlesAuthorization;

class PettyCashFundPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PettyCashFund');
    }

    public function view(AuthUser $authUser, PettyCashFund $pettyCashFund): bool
    {
        return $authUser->can('View:PettyCashFund');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PettyCashFund');
    }

    public function update(AuthUser $authUser, PettyCashFund $pettyCashFund): bool
    {
        return $authUser->can('Update:PettyCashFund');
    }

    public function delete(AuthUser $authUser, PettyCashFund $pettyCashFund): bool
    {
        return $authUser->can('Delete:PettyCashFund');
    }

    public function restore(AuthUser $authUser, PettyCashFund $pettyCashFund): bool
    {
        return $authUser->can('Restore:PettyCashFund');
    }

    public function forceDelete(AuthUser $authUser, PettyCashFund $pettyCashFund): bool
    {
        return $authUser->can('ForceDelete:PettyCashFund');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PettyCashFund');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PettyCashFund');
    }

    public function replicate(AuthUser $authUser, PettyCashFund $pettyCashFund): bool
    {
        return $authUser->can('Replicate:PettyCashFund');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PettyCashFund');
    }

}