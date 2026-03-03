<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DonorCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class DonorCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DonorCategory');
    }

    public function view(AuthUser $authUser, DonorCategory $donorCategory): bool
    {
        return $authUser->can('View:DonorCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DonorCategory');
    }

    public function update(AuthUser $authUser, DonorCategory $donorCategory): bool
    {
        return $authUser->can('Update:DonorCategory');
    }

    public function delete(AuthUser $authUser, DonorCategory $donorCategory): bool
    {
        return $authUser->can('Delete:DonorCategory');
    }

    public function restore(AuthUser $authUser, DonorCategory $donorCategory): bool
    {
        return $authUser->can('Restore:DonorCategory');
    }

    public function forceDelete(AuthUser $authUser, DonorCategory $donorCategory): bool
    {
        return $authUser->can('ForceDelete:DonorCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DonorCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DonorCategory');
    }

    public function replicate(AuthUser $authUser, DonorCategory $donorCategory): bool
    {
        return $authUser->can('Replicate:DonorCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DonorCategory');
    }

}