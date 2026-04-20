<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\GeneralLedger;
use Illuminate\Auth\Access\HandlesAuthorization;

class GeneralLedgerPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GeneralLedger');
    }

    public function view(AuthUser $authUser, GeneralLedger $generalLedger): bool
    {
        return $authUser->can('View:GeneralLedger');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GeneralLedger');
    }

    public function update(AuthUser $authUser, GeneralLedger $generalLedger): bool
    {
        return $authUser->can('Update:GeneralLedger');
    }

    public function delete(AuthUser $authUser, GeneralLedger $generalLedger): bool
    {
        return $authUser->can('Delete:GeneralLedger');
    }

    public function restore(AuthUser $authUser, GeneralLedger $generalLedger): bool
    {
        return $authUser->can('Restore:GeneralLedger');
    }

    public function forceDelete(AuthUser $authUser, GeneralLedger $generalLedger): bool
    {
        return $authUser->can('ForceDelete:GeneralLedger');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:GeneralLedger');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:GeneralLedger');
    }

    public function replicate(AuthUser $authUser, GeneralLedger $generalLedger): bool
    {
        return $authUser->can('Replicate:GeneralLedger');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:GeneralLedger');
    }

}