<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\Cashier;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashierPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Cashier');
    }

    public function view(AuthUser $authUser, Cashier $cashier): bool
    {
        return $authUser->can('View:Cashier');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Cashier');
    }

    public function update(AuthUser $authUser, Cashier $cashier): bool
    {
        return $authUser->can('Update:Cashier');
    }

    public function delete(AuthUser $authUser, Cashier $cashier): bool
    {
        return $authUser->can('Delete:Cashier');
    }

    public function restore(AuthUser $authUser, Cashier $cashier): bool
    {
        return $authUser->can('Restore:Cashier');
    }

    public function forceDelete(AuthUser $authUser, Cashier $cashier): bool
    {
        return $authUser->can('ForceDelete:Cashier');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Cashier');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Cashier');
    }

    public function replicate(AuthUser $authUser, Cashier $cashier): bool
    {
        return $authUser->can('Replicate:Cashier');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Cashier');
    }

}