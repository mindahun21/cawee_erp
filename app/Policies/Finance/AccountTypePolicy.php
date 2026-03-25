<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\AccountType;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AccountType');
    }

    public function view(AuthUser $authUser, AccountType $accountType): bool
    {
        return $authUser->can('View:AccountType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AccountType');
    }

    public function update(AuthUser $authUser, AccountType $accountType): bool
    {
        return $authUser->can('Update:AccountType');
    }

    public function delete(AuthUser $authUser, AccountType $accountType): bool
    {
        return $authUser->can('Delete:AccountType');
    }

    public function restore(AuthUser $authUser, AccountType $accountType): bool
    {
        return $authUser->can('Restore:AccountType');
    }

    public function forceDelete(AuthUser $authUser, AccountType $accountType): bool
    {
        return $authUser->can('ForceDelete:AccountType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AccountType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AccountType');
    }

    public function replicate(AuthUser $authUser, AccountType $accountType): bool
    {
        return $authUser->can('Replicate:AccountType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AccountType');
    }

}