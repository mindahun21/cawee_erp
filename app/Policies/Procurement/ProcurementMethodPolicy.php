<?php

declare(strict_types=1);

namespace App\Policies\Procurement;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Procurement\ProcurementMethod;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcurementMethodPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProcurementMethod');
    }

    public function view(AuthUser $authUser, ProcurementMethod $procurementMethod): bool
    {
        return $authUser->can('View:ProcurementMethod');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProcurementMethod');
    }

    public function update(AuthUser $authUser, ProcurementMethod $procurementMethod): bool
    {
        return $authUser->can('Update:ProcurementMethod');
    }

    public function delete(AuthUser $authUser, ProcurementMethod $procurementMethod): bool
    {
        return $authUser->can('Delete:ProcurementMethod');
    }

    public function restore(AuthUser $authUser, ProcurementMethod $procurementMethod): bool
    {
        return $authUser->can('Restore:ProcurementMethod');
    }

    public function forceDelete(AuthUser $authUser, ProcurementMethod $procurementMethod): bool
    {
        return $authUser->can('ForceDelete:ProcurementMethod');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProcurementMethod');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProcurementMethod');
    }

    public function replicate(AuthUser $authUser, ProcurementMethod $procurementMethod): bool
    {
        return $authUser->can('Replicate:ProcurementMethod');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProcurementMethod');
    }

}