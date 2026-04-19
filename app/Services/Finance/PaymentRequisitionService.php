<?php

namespace App\Services\Finance;

use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\ApprovalHistory;
use App\Models\Finance\BankAccount;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\FinanceSetting;
use App\Models\Finance\IncomeRegister;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use App\Models\Finance\Loan;
use App\Models\Finance\LoanRepaymentSchedule;
use App\Models\Finance\PaymentRequisition;
use App\Models\Finance\PaymentVoucher;
use Illuminate\Support\Facades\DB;

/**
 * PaymentRequisitionService
 *
 * Orchestrates the core AP cycle:
 *   • PR auto-numbering
 *   • PR → PV conversion (approved PRs spawn pre-filled payment vouchers)
 *   • IncomeRegister → GL posting
 *   • Loan repayment recording (marks installment, updates outstanding balance, posts JE)
 */
class PaymentRequisitionService
{
    public function __construct(
        private readonly JournalEntryService $jeService,
        private readonly VoucherService $voucherService,
    ) {}

    // ─────────────────────────────────────────────────────────────────
    // Auto-numbering
    // ─────────────────────────────────────────────────────────────────

    public function generatePrNumber(int $year = null): string
    {
        $year   = $year ?? now()->year;
        $prefix = FinanceSetting::get('pr_number_prefix', 'PR');
        $like   = "{$prefix}-{$year}-%";

        $last = PaymentRequisition::withTrashed()
            ->where('pr_number', 'like', $like)
            ->orderByRaw('LENGTH(pr_number) DESC')
            ->orderBy('pr_number', 'desc')
            ->value('pr_number');

        $seq = $last ? ((int) last(explode('-', $last))) + 1 : 1;
        return sprintf('%s-%d-%04d', $prefix, $year, $seq);
    }

    public function generateIrReference(int $year = null): string
    {
        $year   = $year ?? now()->year;
        $prefix = FinanceSetting::get('ir_number_prefix', 'IR');
        $like   = "{$prefix}-{$year}-%";

        $last = IncomeRegister::withTrashed()
            ->where('reference', 'like', $like)
            ->orderByRaw('LENGTH(reference) DESC')
            ->orderBy('reference', 'desc')
            ->value('reference');

        $seq = $last ? ((int) last(explode('-', $last))) + 1 : 1;
        return sprintf('%s-%d-%04d', $prefix, $year, $seq);
    }

    public function generateLoanReference(int $year = null): string
    {
        $year   = $year ?? now()->year;
        $prefix = FinanceSetting::get('loan_number_prefix', 'LN');
        $like   = "{$prefix}-{$year}-%";

        $last = Loan::withTrashed()
            ->where('loan_reference', 'like', $like)
            ->orderByRaw('LENGTH(loan_reference) DESC')
            ->orderBy('loan_reference', 'desc')
            ->value('loan_reference');

        $seq = $last ? ((int) last(explode('-', $last))) + 1 : 1;
        return sprintf('%s-%d-%04d', $prefix, $year, $seq);
    }

    // ─────────────────────────────────────────────────────────────────
    // PR → Payment Voucher Conversion
    // ─────────────────────────────────────────────────────────────────

    /**
     * Convert an approved Payment Requisition into a Payment Voucher.
     *
     * Pre-conditions:
     *   • PR must be in 'approved' status
     *   • PR must not already have a linked PV
     *
     * Post-conditions:
     *   • A new draft PV is created, pre-filled from PR data
     *   • PR.payment_voucher_id is set to the new PV's ID
     *   • ApprovalHistory entry logged: "Converted to PV"
     *
     * @throws \RuntimeException if PR is not approved or already converted
     */
    public function convertToPv(PaymentRequisition $pr, int $bankAccountId): PaymentVoucher
    {
        if (! $pr->isApproved()) {
            throw new \RuntimeException(
                "Only approved PRs can be converted to a Payment Voucher. Current status: {$pr->status}."
            );
        }

        if ($pr->payment_voucher_id) {
            throw new \RuntimeException(
                "This PR has already been converted to Payment Voucher #{$pr->payment_voucher_id}."
            );
        }

        $pv = null;

        DB::transaction(function () use ($pr, $bankAccountId, &$pv) {
            $pvNumber  = $this->voucherService->generatePvReference(now()->year);
            $openPeriod = AccountingPeriod::current();

            $pv = PaymentVoucher::create([
                'pv_number'              => $pvNumber,
                'accounting_period_id'   => $openPeriod?->id,
                'payment_date'           => today(),
                'payee_name'             => $pr->payee_name,
                'payee_type'             => 'supplier',
                'payee_tin'              => $pr->payee_tin,
                'bank_account_id'        => $bankAccountId,
                'payment_method'         => 'bank_transfer',
                'gross_amount'           => $pr->total_amount,
                'currency_id'            => $pr->currency_id,
                'exchange_rate_to_base'  => $pr->exchange_rate_to_base,
                'withholding_tax_rate'   => 0,
                'withholding_tax_amount' => $pr->withholding_tax_amount,
                'vat_type'               => 'none',
                'vat_rate'               => 0,
                'vat_amount'             => $pr->vat_amount,
                'net_amount'             => $pr->net_payable,
                'cost_center_id'         => $pr->cost_center_id,
                'project_id'             => $pr->project_id,
                'donor_id'               => $pr->donor_id,
                'activity_code'          => $pr->activity_code,
                'donor_code'             => $pr->donor_code,
                'payment_requisition_id' => $pr->id,
                'invoice_number'         => $pr->invoice_number,
                'invoice_date'           => $pr->invoice_date,
                'status'                 => 'draft',
                'prepared_by'            => auth()->id(),
                'notes'                  => "Auto-generated from PR #{$pr->pr_number}",
            ]);

            // Link PV back to PR
            $pr->forceFill(['payment_voucher_id' => $pv->id])->save();

            // Log the conversion
            ApprovalHistory::log(
                $pr,
                'forwarded',
                'Converted to Payment Voucher',
                3,
                'approved',
                'approved',
                "Payment Voucher {$pvNumber} created."
            );
        });

        return $pv;
    }

    // ─────────────────────────────────────────────────────────────────
    // Income Register → GL Posting
    // ─────────────────────────────────────────────────────────────────

    /**
     * Post a confirmed Income Register to the General Ledger.
     *
     * GL entry:
     *   DR: Bank Account COA (or Accounts Receivable if no bank)
     *   CR: Income Account (determined from income_type or default COA setting)
     *
     * @throws \RuntimeException if IR is not confirmed or already posted
     */
    public function postIncomeRegister(IncomeRegister $ir): JournalEntry
    {
        if (! $ir->isConfirmed()) {
            throw new \RuntimeException(
                "Only confirmed Income Registers can be posted. Current status: {$ir->status}."
            );
        }

        if ($ir->journal_entry_id) {
            throw new \RuntimeException('This Income Register has already been posted to the GL.');
        }

        $jeRef     = $this->jeService->generateReference(now()->year);
        $period    = AccountingPeriod::current()
            ?? throw new \RuntimeException('No open accounting period found. Please open a period before posting.');

        // Resolve COA accounts
        $bankCoa    = $ir->bankAccount?->chart_of_account_id
            ?? ChartOfAccount::where('code', FinanceSetting::get('default_ar_account', '1200'))->value('id');

        $incomeCoa  = match($ir->income_type) {
            'grant'       => ChartOfAccount::where('code', FinanceSetting::get('grant_income_account', '4100'))->value('id'),
            'service_fee' => ChartOfAccount::where('code', FinanceSetting::get('service_fee_account', '4200'))->value('id'),
            'interest'    => ChartOfAccount::where('code', FinanceSetting::get('interest_income_account', '4300'))->value('id'),
            default       => ChartOfAccount::where('code', FinanceSetting::get('other_income_account', '4900'))->value('id'),
        };

        if (! $bankCoa || ! $incomeCoa) {
            throw new \RuntimeException(
                'Required COA accounts are not configured. Check Finance Settings for income account mappings.'
            );
        }

        $amount = (float) $ir->amount_in_base ?: ((float) $ir->amount * (float) $ir->exchange_rate_to_base);

        $je = null;

        DB::transaction(function () use ($ir, $jeRef, $period, $bankCoa, $incomeCoa, $amount, &$je) {
            $je = JournalEntry::create([
                'reference_number'     => $jeRef,
                'accounting_period_id' => $period->id,
                'transaction_date'     => $ir->income_date,
                'description'          => "Income Register {$ir->reference} — {$ir->source_name}",
                'status'               => 'approved',
                'source'               => 'crv',
                'source_type'          => get_class($ir),
                'source_id'            => $ir->id,
                'prepared_by'          => auth()->id(),
                'approved_by'          => auth()->id(),
                'currency_id'          => $ir->currency_id,
                'exchange_rate_to_base'=> $ir->exchange_rate_to_base,
            ]);

            // DR Bank / AR Account
            JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id'       => $bankCoa,
                'debit'            => $amount,
                'credit'           => 0,
                'cost_center_id'   => $ir->cost_center_id,
                'project_id'       => $ir->project_id,
                'donor_id'         => $ir->donor_id,
                'narration'        => "Income receipt — {$ir->source_name}",
            ]);

            // CR Income Account
            JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id'       => $incomeCoa,
                'debit'            => 0,
                'credit'           => $amount,
                'cost_center_id'   => $ir->cost_center_id,
                'project_id'       => $ir->project_id,
                'donor_id'         => $ir->donor_id,
                'narration'        => ucfirst(str_replace('_', ' ', $ir->income_type)) . " income — {$ir->source_name}",
            ]);

            $je->load('lines');
            $this->jeService->post($je, auth()->user());

            $ir->forceFill([
                'status'          => 'posted',
                'journal_entry_id'=> $je->id,
            ])->save();
        });

        return $je;
    }

    // ─────────────────────────────────────────────────────────────────
    // Loan Repayment Recording
    // ─────────────────────────────────────────────────────────────────

    /**
     * Record a repayment against a loan installment.
     *
     * Actions:
     *   1. Validates installment is not already fully paid
     *   2. Updates installment paid_amount / paid_date / status
     *   3. Decrements Loan.outstanding_balance
     *   4. Marks loan as fully_paid if all installments are settled
     *   5. Creates & posts a GL journal entry:
     *         DR: Loan Receivable Account
     *         CR: Bank Account
     *
     * @throws \RuntimeException on invalid state
     */
    public function recordRepayment(
        LoanRepaymentSchedule $installment,
        float $paidAmount,
        int $bankAccountId,
        string $notes = ''
    ): JournalEntry {
        if ($installment->isPaid()) {
            throw new \RuntimeException('This installment is already fully paid.');
        }

        $loan = $installment->loan;

        if (! $loan->isActive()) {
            throw new \RuntimeException(
                "Loan {$loan->loan_reference} is {$loan->status} — repayments cannot be recorded."
            );
        }

        $balanceDue = $installment->balanceDue();
        if ($paidAmount > $balanceDue + 0.01) {
            throw new \RuntimeException(
                "Payment of " . number_format($paidAmount, 2) .
                " exceeds the outstanding installment balance of " . number_format($balanceDue, 2) . "."
            );
        }

        $period   = AccountingPeriod::current()
            ?? throw new \RuntimeException('No open accounting period found.');
        $bankAcct = BankAccount::findOrFail($bankAccountId);

        $loanCoa  = ChartOfAccount::where('code', FinanceSetting::get('loan_receivable_account', '1400'))->value('id')
            ?? throw new \RuntimeException('Loan Receivable COA account not configured in Finance Settings.');
        $bankCoa  = $bankAcct->chart_of_account_id
            ?? throw new \RuntimeException("Bank account has no linked COA account.");

        $jeRef = $this->jeService->generateReference(now()->year);
        $je    = null;

        DB::transaction(function () use (
            $installment, $loan, $paidAmount, $bankAccountId,
            $notes, $period, $bankAcct, $loanCoa, $bankCoa, $jeRef, &$je
        ) {
            $newPaid = (float) $installment->paid_amount + $paidAmount;
            $isFullyPaid = $newPaid >= ((float) $installment->total_due - 0.01);

            // 1. Update the installment
            $installment->forceFill([
                'paid_amount' => $newPaid,
                'paid_date'   => today(),
                'status'      => $isFullyPaid ? 'paid' : 'partially_paid',
            ])->save();

            // 2. Decrement loan outstanding balance
            $loan->decrement('outstanding_balance', $paidAmount);
            $loan->refresh();

            // 3. Check if loan is fully paid
            $anyPending = $loan->schedule()
                ->whereIn('status', ['pending', 'partially_paid'])
                ->exists();

            if (! $anyPending) {
                $loan->forceFill(['status' => 'fully_paid'])->save();
            }

            // 4. Create & post GL entry
            $je = JournalEntry::create([
                'reference_number'     => $jeRef,
                'accounting_period_id' => $period->id,
                'transaction_date'     => today(),
                'description'          => "Loan repayment — {$loan->loan_reference} Installment #{$installment->installment_number}",
                'status'               => 'approved',
                'source'               => 'bank',
                'source_type'          => get_class($loan),
                'source_id'            => $loan->id,
                'prepared_by'          => auth()->id(),
                'approved_by'          => auth()->id(),
                'currency_id'          => $loan->currency_id,
                'exchange_rate_to_base'=> 1,
                'notes'                => $notes,
            ]);

            // DR: Loan Receivable (reduces the asset)
            JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id'       => $bankCoa,
                'debit'            => $paidAmount,
                'credit'           => 0,
                'narration'        => "Loan repayment received — {$loan->loan_reference}",
            ]);

            // CR: Bank Account (cash out)
            JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id'       => $loanCoa,
                'debit'            => 0,
                'credit'           => $paidAmount,
                'narration'        => "Loan principal repayment — installment #{$installment->installment_number}",
            ]);

            $je->load('lines');
            $this->jeService->post($je, auth()->user());

            $installment->forceFill(['journal_entry_id' => $je->id])->save();
        });

        return $je;
    }
}
