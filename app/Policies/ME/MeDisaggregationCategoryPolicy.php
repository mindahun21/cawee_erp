<?php

declare(strict_types=1);

namespace App\Policies\ME;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ME\MeDisaggregationCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeDisaggregationCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MeDisaggregationCategory');
    }

    public function view(AuthUser $authUser, MeDisaggregationCategory $meDisaggregationCategory): bool
    {
        return $authUser->can('View:MeDisaggregationCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MeDisaggregationCategory');
    }

    public function update(AuthUser $authUser, MeDisaggregationCategory $meDisaggregationCategory): bool
    {
        return $authUser->can('Update:MeDisaggregationCategory');
    }

    public function delete(AuthUser $authUser, MeDisaggregationCategory $meDisaggregationCategory): bool
    {
        return $authUser->can('Delete:MeDisaggregationCategory');
    }

    public function restore(AuthUser $authUser, MeDisaggregationCategory $meDisaggregationCategory): bool
    {
        return $authUser->can('Restore:MeDisaggregationCategory');
    }

    public function forceDelete(AuthUser $authUser, MeDisaggregationCategory $meDisaggregationCategory): bool
    {
        return $authUser->can('ForceDelete:MeDisaggregationCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MeDisaggregationCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MeDisaggregationCategory');
    }

    public function replicate(AuthUser $authUser, MeDisaggregationCategory $meDisaggregationCategory): bool
    {
        return $authUser->can('Replicate:MeDisaggregationCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MeDisaggregationCategory');
    }

}