<?php

namespace App\Models\Finance;

use App\Models\Donor;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends Model
{
    protected $table = 'finance_journal_entry_lines';

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'cost_center_id',
        'donor_id',
        'project_id',
        'activity_code',
        'vendor_name',
        'narration',
    ];

    protected function casts(): array
    {
        return [
            'debit'  => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    // ── Computed helpers ──────────────────────────────────────────────

    /**
     * Returns the net movement for this line relative to the account's
     * normal balance direction.
     *   Debit-normal  → debit − credit
     *   Credit-normal → credit − debit
     */
    public function netMovement(): float
    {
        $normalBalance = $this->account?->accountType?->normal_balance ?? 'debit';

        return $normalBalance === 'debit'
            ? (float) $this->debit - (float) $this->credit
            : (float) $this->credit - (float) $this->debit;
    }

    /**
     * True if this line has a debit amount (and zero credit).
     */
    public function isDebitLine(): bool
    {
        return (float) $this->debit > 0;
    }

    /**
     * True if this line has a credit amount (and zero debit).
     */
    public function isCreditLine(): bool
    {
        return (float) $this->credit > 0;
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    /**
     * Dimension 1 — Cost Center
     */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    /**
     * Dimension 2 — Donor
     */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class, 'donor_id');
    }

    /**
     * Dimension 3 — Project (maps to hr_projects table via App\Models\Project)
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    // Dimension 4 is activity_code (plain string — no FK, intentional)
}
