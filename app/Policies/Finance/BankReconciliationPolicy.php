<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\BankReconciliation;
use Illuminate\Auth\Access\HandlesAuthorization;

class BankReconciliationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BankReconciliation');
    }

    public function view(AuthUser $authUser, BankReconciliation $bankReconciliation): bool
    {
        return $authUser->can('View:BankReconciliation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BankReconciliation');
    }

    public function update(AuthUser $authUser, BankReconciliation $bankReconciliation): bool
    {
        return $authUser->can('Update:BankReconciliation');
    }

    public function delete(AuthUser $authUser, BankReconciliation $bankReconciliation): bool
    {
        return $authUser->can('Delete:BankReconciliation');
    }

    public function restore(AuthUser $authUser, BankReconciliation $bankReconciliation): bool
    {
        return $authUser->can('Restore:BankReconciliation');
    }

    public function forceDelete(AuthUser $authUser, BankReconciliation $bankReconciliation): bool
    {
        return $authUser->can('ForceDelete:BankReconciliation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BankReconciliation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BankReconciliation');
    }

    public function replicate(AuthUser $authUser, BankReconciliation $bankReconciliation): bool
    {
        return $authUser->can('Replicate:BankReconciliation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BankReconciliation');
    }

}