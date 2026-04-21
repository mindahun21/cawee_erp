<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\ProjectProgressPayment;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectProgressPaymentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProjectProgressPayment');
    }

    public function view(AuthUser $authUser, ProjectProgressPayment $projectProgressPayment): bool
    {
        return $authUser->can('View:ProjectProgressPayment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProjectProgressPayment');
    }

    public function update(AuthUser $authUser, ProjectProgressPayment $projectProgressPayment): bool
    {
        return $authUser->can('Update:ProjectProgressPayment');
    }

    public function delete(AuthUser $authUser, ProjectProgressPayment $projectProgressPayment): bool
    {
        return $authUser->can('Delete:ProjectProgressPayment');
    }

    public function restore(AuthUser $authUser, ProjectProgressPayment $projectProgressPayment): bool
    {
        return $authUser->can('Restore:ProjectProgressPayment');
    }

    public function forceDelete(AuthUser $authUser, ProjectProgressPayment $projectProgressPayment): bool
    {
        return $authUser->can('ForceDelete:ProjectProgressPayment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProjectProgressPayment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProjectProgressPayment');
    }

    public function replicate(AuthUser $authUser, ProjectProgressPayment $projectProgressPayment): bool
    {
        return $authUser->can('Replicate:ProjectProgressPayment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProjectProgressPayment');
    }

}