<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TravelAdvance;
use Illuminate\Auth\Access\HandlesAuthorization;

class TravelAdvancePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TravelAdvance');
    }

    public function view(AuthUser $authUser, TravelAdvance $travelAdvance): bool
    {
        return $authUser->can('View:TravelAdvance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TravelAdvance');
    }

    public function update(AuthUser $authUser, TravelAdvance $travelAdvance): bool
    {
        return $authUser->can('Update:TravelAdvance');
    }

    public function delete(AuthUser $authUser, TravelAdvance $travelAdvance): bool
    {
        return $authUser->can('Delete:TravelAdvance');
    }

    public function restore(AuthUser $authUser, TravelAdvance $travelAdvance): bool
    {
        return $authUser->can('Restore:TravelAdvance');
    }

    public function forceDelete(AuthUser $authUser, TravelAdvance $travelAdvance): bool
    {
        return $authUser->can('ForceDelete:TravelAdvance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TravelAdvance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TravelAdvance');
    }

    public function replicate(AuthUser $authUser, TravelAdvance $travelAdvance): bool
    {
        return $authUser->can('Replicate:TravelAdvance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TravelAdvance');
    }

}