<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AppraisalTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppraisalTemplatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AppraisalTemplate');
    }

    public function view(AuthUser $authUser, AppraisalTemplate $appraisalTemplate): bool
    {
        return $authUser->can('View:AppraisalTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AppraisalTemplate');
    }

    public function update(AuthUser $authUser, AppraisalTemplate $appraisalTemplate): bool
    {
        return $authUser->can('Update:AppraisalTemplate');
    }

    public function delete(AuthUser $authUser, AppraisalTemplate $appraisalTemplate): bool
    {
        return $authUser->can('Delete:AppraisalTemplate');
    }

    public function restore(AuthUser $authUser, AppraisalTemplate $appraisalTemplate): bool
    {
        return $authUser->can('Restore:AppraisalTemplate');
    }

    public function forceDelete(AuthUser $authUser, AppraisalTemplate $appraisalTemplate): bool
    {
        return $authUser->can('ForceDelete:AppraisalTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AppraisalTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AppraisalTemplate');
    }

    public function replicate(AuthUser $authUser, AppraisalTemplate $appraisalTemplate): bool
    {
        return $authUser->can('Replicate:AppraisalTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AppraisalTemplate');
    }

}