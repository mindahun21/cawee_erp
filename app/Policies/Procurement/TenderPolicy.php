<?php

declare(strict_types=1);

namespace App\Policies\Procurement;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Procurement\Tender;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenderPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Tender');
    }

    public function view(AuthUser $authUser, Tender $tender): bool
    {
        return $authUser->can('View:Tender');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Tender');
    }

    public function update(AuthUser $authUser, Tender $tender): bool
    {
        return $authUser->can('Update:Tender');
    }

    public function delete(AuthUser $authUser, Tender $tender): bool
    {
        return $authUser->can('Delete:Tender');
    }

    public function restore(AuthUser $authUser, Tender $tender): bool
    {
        return $authUser->can('Restore:Tender');
    }

    public function forceDelete(AuthUser $authUser, Tender $tender): bool
    {
        return $authUser->can('ForceDelete:Tender');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Tender');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Tender');
    }

    public function replicate(AuthUser $authUser, Tender $tender): bool
    {
        return $authUser->can('Replicate:Tender');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Tender');
    }

}