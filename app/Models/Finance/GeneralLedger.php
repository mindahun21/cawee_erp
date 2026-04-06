<?php

namespace App\Models\Finance;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GeneralLedger
 *
 * Append-only, denormalized running ledger.
 *
 * One row is created for every finance_journal_entry_lines row when its
 * parent JournalEntry transitions to status = 'posted'.
 *
 * Design principles:
 *  • Never soft-deleted — the GL is the permanent, immutable accounting record.
 *  • running_balance is always expressed relative to the account's normal balance:
 *      Debit-normal  (asset, expense):            += debit  − credit
 *      Credit-normal (liability, equity, income): += credit − debit
 *    A positive running_balance always means the account is "on its normal side".
 *  • Rows must NEVER be updated or deleted after creation — any correction must
 *    be accomplished by posting a new JournalEntry (or reversal).
 *
 * @property int    $id
 * @property int    $account_id
 * @property int    $journal_entry_line_id
 * @property string $transaction_date
 * @property float  $debit
 * @property float  $credit
 * @property float  $running_balance
 * @property int    $currency_id
 * @property int    $period_id
 */
class GeneralLedger extends Model
{
    protected $table = 'finance_general_ledgers';

    /**
     * The GL is append-only — no updates are ever applied to existing rows.
     * Disabling mass-assignment protection intentionally here because all writes
     * go through GeneralLedgerService which validates inputs before calling create().
     */
    protected $fillable = [
        'account_id',
        'journal_entry_line_id',
        'transaction_date',
        'period_id',
        'debit',
        'credit',
        'running_balance',
        'currency_id',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'debit'            => 'decimal:2',
            'credit'           => 'decimal:2',
            'running_balance'  => 'decimal:2',
        ];
    }

    // ── Computed helpers ───────────────────────────────────────────────

    /**
     * Returns the net movement on this row relative to the account's
     * normal balance. Positive = account increased, negative = decreased.
     */
    public function netMovement(): float
    {
        $normalBalance = $this->account?->accountType?->normal_balance ?? 'debit';

        return $normalBalance === 'debit'
            ? (float) $this->debit  - (float) $this->credit
            : (float) $this->credit - (float) $this->debit;
    }

    /**
     * Whether this row represents a debit posting (debit > 0).
     */
    public function isDebit(): bool
    {
        return (float) $this->debit > 0;
    }

    /**
     * Whether this row represents a credit posting (credit > 0).
     */
    public function isCredit(): bool
    {
        return (float) $this->credit > 0;
    }

    // ── Static query helpers ───────────────────────────────────────────

    /**
     * Return the latest running balance for a given account.
     * Returns 0.0 when the account has no GL history.
     */
    public static function latestBalance(int $accountId): float
    {
        return (float) static::where('account_id', $accountId)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->value('running_balance') ?? 0.0;
    }

    /**
     * Return the running balance for a given account as at a specific date.
     * Includes all postings up to and including that date.
     */
    public static function balanceAsOf(int $accountId, string $date): float
    {
        return (float) static::where('account_id', $accountId)
            ->where('transaction_date', '<=', $date)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->value('running_balance') ?? 0.0;
    }

    /**
     * Return total debits posted to an account within a date range.
     */
    public static function totalDebits(int $accountId, string $from, string $to): float
    {
        return (float) static::where('account_id', $accountId)
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('debit');
    }

    /**
     * Return total credits posted to an account within a date range.
     */
    public static function totalCredits(int $accountId, string $from, string $to): float
    {
        return (float) static::where('account_id', $accountId)
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('credit');
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    /**
     * Scope to a specific accounting period.
     */
    public function scopeForPeriod($query, int $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    /**
     * Scope to a date range.
     */
    public function scopeInDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    /**
     * Scope to a specific cost center via the linked JournalEntryLine.
     */
    public function scopeForCostCenter($query, int $costCenterId)
    {
        return $query->whereHas('journalEntryLine', function ($q) use ($costCenterId) {
            $q->where('cost_center_id', $costCenterId);
        });
    }

    /**
     * Scope to a specific donor via the linked JournalEntryLine.
     */
    public function scopeForDonor($query, int $donorId)
    {
        return $query->whereHas('journalEntryLine', function ($q) use ($donorId) {
            $q->where('donor_id', $donorId);
        });
    }

    /**
     * Scope to a specific project via the linked JournalEntryLine.
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->whereHas('journalEntryLine', function ($q) use ($projectId) {
            $q->where('project_id', $projectId);
        });
    }

    // ── Relationships ──────────────────────────────────────────────────

    /**
     * The Chart of Accounts entry this GL row belongs to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    /**
     * The individual journal entry line that generated this GL row.
     * Through this relationship you can reach the parent JournalEntry
     * and the 4-dimension NGO coding (cost_center, donor, project, activity_code).
     */
    public function journalEntryLine(): BelongsTo
    {
        return $this->belongsTo(JournalEntryLine::class, 'journal_entry_line_id');
    }

    /**
     * The transaction currency for this posting.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * The accounting period this posting was made in.
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }
}
