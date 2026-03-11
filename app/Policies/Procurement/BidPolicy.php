<?php

declare(strict_types=1);

namespace App\Policies\Procurement;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Procurement\Bid;
use Illuminate\Auth\Access\HandlesAuthorization;

class BidPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Bid');
    }

    public function view(AuthUser $authUser, Bid $bid): bool
    {
        return $authUser->can('View:Bid');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Bid');
    }

    public function update(AuthUser $authUser, Bid $bid): bool
    {
        return $authUser->can('Update:Bid');
    }

    public function delete(AuthUser $authUser, Bid $bid): bool
    {
        return $authUser->can('Delete:Bid');
    }

    public function restore(AuthUser $authUser, Bid $bid): bool
    {
        return $authUser->can('Restore:Bid');
    }

    public function forceDelete(AuthUser $authUser, Bid $bid): bool
    {
        return $authUser->can('ForceDelete:Bid');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Bid');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Bid');
    }

    public function replicate(AuthUser $authUser, Bid $bid): bool
    {
        return $authUser->can('Replicate:Bid');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Bid');
    }

}