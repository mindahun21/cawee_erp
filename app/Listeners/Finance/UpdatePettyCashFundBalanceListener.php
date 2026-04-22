<?php

namespace App\Listeners\Finance;

use App\Models\Finance\PettyCashFund;
use Illuminate\Support\Facades\Log;

/**
 * Updates the PettyCashFund balance when a replenishment is approved.
 *
 * Triggered by: PettyCashReplenishmentApproved event
 * Effect: fund.current_balance += replenishment.amount_approved
 */
class UpdatePettyCashFundBalanceListener
{
    public function handle(object $event): void
    {
        $replenishment = $event->replenishment ?? null;

        if (! $replenishment) {
            Log::warning('Finance: UpdatePettyCashFundBalanceListener — no replenishment on event');
            return;
        }

        $fund = PettyCashFund::find($replenishment->petty_cash_fund_id);

        if (! $fund) {
            Log::error('Finance: PettyCashFund not found', ['id' => $replenishment->petty_cash_fund_id]);
            return;
        }

        $approvedAmount = (float)($replenishment->amount_approved ?? $replenishment->amount_requested ?? 0);

        $fund->increment('current_balance', $approvedAmount);

        // Update replenishment status to disbursed
        $replenishment->update([
            'status'       => 'disbursed',
            'disbursed_at' => now(),
        ]);

        Log::info('Finance: PettyCashFund balance updated after replenishment', [
            'fund_id'          => $fund->id,
            'replenishment_id' => $replenishment->id,
            'amount_added'     => $approvedAmount,
            'new_balance'      => $fund->fresh()->current_balance,
        ]);
    }
}
