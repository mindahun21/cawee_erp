<?php

declare(strict_types=1);

namespace App\Policies\Procurement;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Procurement\ProcurementBidSecurity;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcurementBidSecurityPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProcurementBidSecurity');
    }

    public function view(AuthUser $authUser, ProcurementBidSecurity $procurementBidSecurity): bool
    {
        return $authUser->can('View:ProcurementBidSecurity');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProcurementBidSecurity');
    }

    public function update(AuthUser $authUser, ProcurementBidSecurity $procurementBidSecurity): bool
    {
        return $authUser->can('Update:ProcurementBidSecurity');
    }

    public function delete(AuthUser $authUser, ProcurementBidSecurity $procurementBidSecurity): bool
    {
        return $authUser->can('Delete:ProcurementBidSecurity');
    }

    public function restore(AuthUser $authUser, ProcurementBidSecurity $procurementBidSecurity): bool
    {
        return $authUser->can('Restore:ProcurementBidSecurity');
    }

    public function forceDelete(AuthUser $authUser, ProcurementBidSecurity $procurementBidSecurity): bool
    {
        return $authUser->can('ForceDelete:ProcurementBidSecurity');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProcurementBidSecurity');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProcurementBidSecurity');
    }

    public function replicate(AuthUser $authUser, ProcurementBidSecurity $procurementBidSecurity): bool
    {
        return $authUser->can('Replicate:ProcurementBidSecurity');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProcurementBidSecurity');
    }

}