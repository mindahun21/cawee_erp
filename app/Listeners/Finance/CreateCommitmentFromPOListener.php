<?php

namespace App\Listeners\Finance;

use App\Models\Finance\Budget;
use App\Models\Finance\BudgetLine;
use App\Models\Finance\Commitment;
use Illuminate\Support\Facades\Log;

/**
 * Creates a Finance Commitment record when a Procurement PO is approved.
 *
 * This wires the Procurement → Finance budget control:
 *   Approved PO → Commitment (encumbered against budget line)
 *
 * Fire via: event(new \App\Events\Procurement\ProcurementPOApproved($po))
 */
class CreateCommitmentFromPOListener
{
    public function handle(object $event): void
    {
        $po = $event->purchaseOrder ?? $event->po ?? null;

        if (! $po) {
            Log::warning('Finance: CreateCommitmentFromPOListener — no PO found on event', [
                'event' => get_class($event),
            ]);
            return;
        }

        // Find the matching active budget for this project + cost center
        $budget = Budget::where('status', 'active')
            ->where('project_id', $po->project_id ?? null)
            ->orWhere('cost_center_id', $po->cost_center_id ?? null)
            ->latest()
            ->first();

        if (! $budget) {
            Log::info('Finance: No matching active budget found for PO commitment', ['po_id' => $po->id]);
            return;
        }

        // Find relevant budget line (match by account or first available)
        $budgetLine = BudgetLine::where('budget_id', $budget->id)->first();

        if (! $budgetLine) {
            return;
        }

        // Create commitment
        Commitment::create([
            'source_type'    => get_class($po),
            'source_id'      => $po->id,
            'budget_id'      => $budget->id,
            'budget_line_id' => $budgetLine->id,
            'amount'         => $po->total_amount ?? 0,
            'currency_id'    => $po->currency_id ?? null,
            'commitment_date'=> now()->toDateString(),
            'status'         => 'open',
        ]);

        // Update budget committed amount
        $budget->increment('committed_amount', (float)($po->total_amount ?? 0));

        Log::info('Finance: Commitment created from PO', [
            'po_id'     => $po->id,
            'budget_id' => $budget->id,
            'amount'    => $po->total_amount,
        ]);
    }
}
