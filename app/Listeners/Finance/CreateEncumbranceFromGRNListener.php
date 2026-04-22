<?php

namespace App\Listeners\Finance;

use App\Models\Finance\Commitment;
use App\Models\Finance\Encumbrance;
use Illuminate\Support\Facades\Log;

/**
 * Creates an Encumbrance record when a Procurement Goods Receipt is confirmed.
 *
 * Flow: PO approved → Commitment → GRN confirmed → Encumbrance
 * The encumbrance liquidates the commitment and becomes actual expense
 * once the Payment Voucher is posted to GL.
 */
class CreateEncumbranceFromGRNListener
{
    public function handle(object $event): void
    {
        $grn = $event->goodsReceiptNote ?? $event->grn ?? null;

        if (! $grn) {
            Log::warning('Finance: CreateEncumbranceFromGRNListener — no GRN on event');
            return;
        }

        // Find open commitment for the associated PO
        $commitment = Commitment::where('source_type', 'like', '%PurchaseOrder%')
            ->where('source_id', $grn->purchase_order_id ?? null)
            ->where('status', 'open')
            ->first();

        if (! $commitment) {
            Log::info('Finance: No open commitment found for GRN', ['grn_id' => $grn->id]);
            return;
        }

        $amount = (float)($grn->total_received_value ?? $grn->total_amount ?? 0);

        Encumbrance::create([
            'commitment_id'   => $commitment->id,
            'source_type'     => get_class($grn),
            'source_id'       => $grn->id,
            'budget_id'       => $commitment->budget_id,
            'budget_line_id'  => $commitment->budget_line_id,
            'amount'          => $amount,
            'currency_id'     => $commitment->currency_id,
            'encumbrance_date'=> now()->toDateString(),
            'status'          => 'open',
        ]);

        // Update budget encumbered amount
        $commitment->budget?->increment('encumbered_amount', $amount);

        // Mark commitment as partially/fully utilized
        $commitment->update(['status' => 'partially_utilized']);

        Log::info('Finance: Encumbrance created from GRN', [
            'grn_id'        => $grn->id,
            'commitment_id' => $commitment->id,
            'amount'        => $amount,
        ]);
    }
}
