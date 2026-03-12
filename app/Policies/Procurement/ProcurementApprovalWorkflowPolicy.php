<?php

declare(strict_types=1);

namespace App\Policies\Procurement;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Procurement\ProcurementApprovalWorkflow;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcurementApprovalWorkflowPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProcurementApprovalWorkflow');
    }

    public function view(AuthUser $authUser, ProcurementApprovalWorkflow $procurementApprovalWorkflow): bool
    {
        return $authUser->can('View:ProcurementApprovalWorkflow');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProcurementApprovalWorkflow');
    }

    public function update(AuthUser $authUser, ProcurementApprovalWorkflow $procurementApprovalWorkflow): bool
    {
        return $authUser->can('Update:ProcurementApprovalWorkflow');
    }

    public function delete(AuthUser $authUser, ProcurementApprovalWorkflow $procurementApprovalWorkflow): bool
    {
        return $authUser->can('Delete:ProcurementApprovalWorkflow');
    }

    public function restore(AuthUser $authUser, ProcurementApprovalWorkflow $procurementApprovalWorkflow): bool
    {
        return $authUser->can('Restore:ProcurementApprovalWorkflow');
    }

    public function forceDelete(AuthUser $authUser, ProcurementApprovalWorkflow $procurementApprovalWorkflow): bool
    {
        return $authUser->can('ForceDelete:ProcurementApprovalWorkflow');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProcurementApprovalWorkflow');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProcurementApprovalWorkflow');
    }

    public function replicate(AuthUser $authUser, ProcurementApprovalWorkflow $procurementApprovalWorkflow): bool
    {
        return $authUser->can('Replicate:ProcurementApprovalWorkflow');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProcurementApprovalWorkflow');
    }

}