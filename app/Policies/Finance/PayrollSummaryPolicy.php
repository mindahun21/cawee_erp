<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\PayrollSummary;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollSummaryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PayrollSummary');
    }

    public function view(AuthUser $authUser, PayrollSummary $payrollSummary): bool
    {
        return $authUser->can('View:PayrollSummary');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PayrollSummary');
    }

    public function update(AuthUser $authUser, PayrollSummary $payrollSummary): bool
    {
        return $authUser->can('Update:PayrollSummary');
    }

    public function delete(AuthUser $authUser, PayrollSummary $payrollSummary): bool
    {
        return $authUser->can('Delete:PayrollSummary');
    }

    public function restore(AuthUser $authUser, PayrollSummary $payrollSummary): bool
    {
        return $authUser->can('Restore:PayrollSummary');
    }

    public function forceDelete(AuthUser $authUser, PayrollSummary $payrollSummary): bool
    {
        return $authUser->can('ForceDelete:PayrollSummary');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PayrollSummary');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PayrollSummary');
    }

    public function replicate(AuthUser $authUser, PayrollSummary $payrollSummary): bool
    {
        return $authUser->can('Replicate:PayrollSummary');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PayrollSummary');
    }

}