<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\BankAdvice;
use Illuminate\Auth\Access\HandlesAuthorization;

class BankAdvicePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BankAdvice');
    }

    public function view(AuthUser $authUser, BankAdvice $bankAdvice): bool
    {
        return $authUser->can('View:BankAdvice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BankAdvice');
    }

    public function update(AuthUser $authUser, BankAdvice $bankAdvice): bool
    {
        return $authUser->can('Update:BankAdvice');
    }

    public function delete(AuthUser $authUser, BankAdvice $bankAdvice): bool
    {
        return $authUser->can('Delete:BankAdvice');
    }

    public function restore(AuthUser $authUser, BankAdvice $bankAdvice): bool
    {
        return $authUser->can('Restore:BankAdvice');
    }

    public function forceDelete(AuthUser $authUser, BankAdvice $bankAdvice): bool
    {
        return $authUser->can('ForceDelete:BankAdvice');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BankAdvice');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BankAdvice');
    }

    public function replicate(AuthUser $authUser, BankAdvice $bankAdvice): bool
    {
        return $authUser->can('Replicate:BankAdvice');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BankAdvice');
    }

}