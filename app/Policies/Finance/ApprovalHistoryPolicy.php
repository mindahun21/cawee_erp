<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\ApprovalHistory;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApprovalHistoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ApprovalHistory');
    }

    public function view(AuthUser $authUser, ApprovalHistory $approvalHistory): bool
    {
        return $authUser->can('View:ApprovalHistory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ApprovalHistory');
    }

    public function update(AuthUser $authUser, ApprovalHistory $approvalHistory): bool
    {
        return $authUser->can('Update:ApprovalHistory');
    }

    public function delete(AuthUser $authUser, ApprovalHistory $approvalHistory): bool
    {
        return $authUser->can('Delete:ApprovalHistory');
    }

    public function restore(AuthUser $authUser, ApprovalHistory $approvalHistory): bool
    {
        return $authUser->can('Restore:ApprovalHistory');
    }

    public function forceDelete(AuthUser $authUser, ApprovalHistory $approvalHistory): bool
    {
        return $authUser->can('ForceDelete:ApprovalHistory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ApprovalHistory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ApprovalHistory');
    }

    public function replicate(AuthUser $authUser, ApprovalHistory $approvalHistory): bool
    {
        return $authUser->can('Replicate:ApprovalHistory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ApprovalHistory');
    }

}