<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\FundTransfer;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundTransferPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FundTransfer');
    }

    public function view(AuthUser $authUser, FundTransfer $fundTransfer): bool
    {
        return $authUser->can('View:FundTransfer');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FundTransfer');
    }

    public function update(AuthUser $authUser, FundTransfer $fundTransfer): bool
    {
        return $authUser->can('Update:FundTransfer');
    }

    public function delete(AuthUser $authUser, FundTransfer $fundTransfer): bool
    {
        return $authUser->can('Delete:FundTransfer');
    }

    public function restore(AuthUser $authUser, FundTransfer $fundTransfer): bool
    {
        return $authUser->can('Restore:FundTransfer');
    }

    public function forceDelete(AuthUser $authUser, FundTransfer $fundTransfer): bool
    {
        return $authUser->can('ForceDelete:FundTransfer');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FundTransfer');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FundTransfer');
    }

    public function replicate(AuthUser $authUser, FundTransfer $fundTransfer): bool
    {
        return $authUser->can('Replicate:FundTransfer');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FundTransfer');
    }

}