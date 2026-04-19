<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\PaymentRequisition;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentRequisitionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PaymentRequisition');
    }

    public function view(AuthUser $authUser, PaymentRequisition $paymentRequisition): bool
    {
        return $authUser->can('View:PaymentRequisition');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PaymentRequisition');
    }

    public function update(AuthUser $authUser, PaymentRequisition $paymentRequisition): bool
    {
        return $authUser->can('Update:PaymentRequisition');
    }

    public function delete(AuthUser $authUser, PaymentRequisition $paymentRequisition): bool
    {
        return $authUser->can('Delete:PaymentRequisition');
    }

    public function restore(AuthUser $authUser, PaymentRequisition $paymentRequisition): bool
    {
        return $authUser->can('Restore:PaymentRequisition');
    }

    public function forceDelete(AuthUser $authUser, PaymentRequisition $paymentRequisition): bool
    {
        return $authUser->can('ForceDelete:PaymentRequisition');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PaymentRequisition');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PaymentRequisition');
    }

    public function replicate(AuthUser $authUser, PaymentRequisition $paymentRequisition): bool
    {
        return $authUser->can('Replicate:PaymentRequisition');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PaymentRequisition');
    }

}