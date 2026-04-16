<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationItem extends Model
{
    protected $table = 'finance_bank_reconciliation_items';

    protected $fillable = [
        'reconciliation_id',
        'journal_entry_line_id',
        'item_type',
        'transaction_date',
        'description',
        'amount',
        'is_cleared',
        'cleared_date',
        'bank_reference',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'cleared_date'     => 'date',
            'amount'           => 'decimal:2',
            'is_cleared'       => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'reconciliation_id');
    }

    public function journalEntryLine(): BelongsTo
    {
        return $this->belongsTo(JournalEntryLine::class, 'journal_entry_line_id');
    }
}
