<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TravelRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class TravelRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TravelRequest');
    }

    public function view(AuthUser $authUser, TravelRequest $travelRequest): bool
    {
        return $authUser->can('View:TravelRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TravelRequest');
    }

    public function update(AuthUser $authUser, TravelRequest $travelRequest): bool
    {
        return $authUser->can('Update:TravelRequest');
    }

    public function delete(AuthUser $authUser, TravelRequest $travelRequest): bool
    {
        return $authUser->can('Delete:TravelRequest');
    }

    public function restore(AuthUser $authUser, TravelRequest $travelRequest): bool
    {
        return $authUser->can('Restore:TravelRequest');
    }

    public function forceDelete(AuthUser $authUser, TravelRequest $travelRequest): bool
    {
        return $authUser->can('ForceDelete:TravelRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TravelRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TravelRequest');
    }

    public function replicate(AuthUser $authUser, TravelRequest $travelRequest): bool
    {
        return $authUser->can('Replicate:TravelRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TravelRequest');
    }

}