<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\PaymentVoucher;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentVoucherPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PaymentVoucher');
    }

    public function view(AuthUser $authUser, PaymentVoucher $paymentVoucher): bool
    {
        return $authUser->can('View:PaymentVoucher');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PaymentVoucher');
    }

    public function update(AuthUser $authUser, PaymentVoucher $paymentVoucher): bool
    {
        return $authUser->can('Update:PaymentVoucher');
    }

    public function delete(AuthUser $authUser, PaymentVoucher $paymentVoucher): bool
    {
        return $authUser->can('Delete:PaymentVoucher');
    }

    public function restore(AuthUser $authUser, PaymentVoucher $paymentVoucher): bool
    {
        return $authUser->can('Restore:PaymentVoucher');
    }

    public function forceDelete(AuthUser $authUser, PaymentVoucher $paymentVoucher): bool
    {
        return $authUser->can('ForceDelete:PaymentVoucher');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PaymentVoucher');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PaymentVoucher');
    }

    public function replicate(AuthUser $authUser, PaymentVoucher $paymentVoucher): bool
    {
        return $authUser->can('Replicate:PaymentVoucher');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PaymentVoucher');
    }

}