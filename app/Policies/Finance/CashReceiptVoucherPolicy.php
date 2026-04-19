<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Finance\CashReceiptVoucher;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashReceiptVoucherPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CashReceiptVoucher');
    }

    public function view(AuthUser $authUser, CashReceiptVoucher $cashReceiptVoucher): bool
    {
        return $authUser->can('View:CashReceiptVoucher');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CashReceiptVoucher');
    }

    public function update(AuthUser $authUser, CashReceiptVoucher $cashReceiptVoucher): bool
    {
        return $authUser->can('Update:CashReceiptVoucher');
    }

    public function delete(AuthUser $authUser, CashReceiptVoucher $cashReceiptVoucher): bool
    {
        return $authUser->can('Delete:CashReceiptVoucher');
    }

    public function restore(AuthUser $authUser, CashReceiptVoucher $cashReceiptVoucher): bool
    {
        return $authUser->can('Restore:CashReceiptVoucher');
    }

    public function forceDelete(AuthUser $authUser, CashReceiptVoucher $cashReceiptVoucher): bool
    {
        return $authUser->can('ForceDelete:CashReceiptVoucher');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CashReceiptVoucher');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CashReceiptVoucher');
    }

    public function replicate(AuthUser $authUser, CashReceiptVoucher $cashReceiptVoucher): bool
    {
        return $authUser->can('Replicate:CashReceiptVoucher');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CashReceiptVoucher');
    }

}