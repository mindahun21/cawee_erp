<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\ReferencePadBook;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReferencePadBookPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReferencePadBook');
    }

    public function view(AuthUser $authUser, ReferencePadBook $referencePadBook): bool
    {
        return $authUser->can('View:ReferencePadBook');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReferencePadBook');
    }

    public function update(AuthUser $authUser, ReferencePadBook $referencePadBook): bool
    {
        return $authUser->can('Update:ReferencePadBook');
    }

    public function delete(AuthUser $authUser, ReferencePadBook $referencePadBook): bool
    {
        return $authUser->can('Delete:ReferencePadBook');
    }

    public function restore(AuthUser $authUser, ReferencePadBook $referencePadBook): bool
    {
        return $authUser->can('Restore:ReferencePadBook');
    }

    public function forceDelete(AuthUser $authUser, ReferencePadBook $referencePadBook): bool
    {
        return $authUser->can('ForceDelete:ReferencePadBook');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReferencePadBook');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReferencePadBook');
    }

    public function replicate(AuthUser $authUser, ReferencePadBook $referencePadBook): bool
    {
        return $authUser->can('Replicate:ReferencePadBook');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReferencePadBook');
    }

}