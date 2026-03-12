<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\OtherSettings;
use Illuminate\Auth\Access\HandlesAuthorization;

class OtherSettingsPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OtherSettings');
    }

    public function view(AuthUser $authUser, OtherSettings $otherSettings): bool
    {
        return $authUser->can('View:OtherSettings');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OtherSettings');
    }

    public function update(AuthUser $authUser, OtherSettings $otherSettings): bool
    {
        return $authUser->can('Update:OtherSettings');
    }

    public function delete(AuthUser $authUser, OtherSettings $otherSettings): bool
    {
        return $authUser->can('Delete:OtherSettings');
    }

    public function restore(AuthUser $authUser, OtherSettings $otherSettings): bool
    {
        return $authUser->can('Restore:OtherSettings');
    }

    public function forceDelete(AuthUser $authUser, OtherSettings $otherSettings): bool
    {
        return $authUser->can('ForceDelete:OtherSettings');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OtherSettings');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OtherSettings');
    }

    public function replicate(AuthUser $authUser, OtherSettings $otherSettings): bool
    {
        return $authUser->can('Replicate:OtherSettings');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OtherSettings');
    }

}