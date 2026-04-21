<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerdiemSettlement extends Model
{
    protected $table = 'finance_perdiem_settlements';

    protected $fillable = [
        'perdiem_request_id', 'settlement_date',
        'actual_days', 'actual_amount', 'advance_paid',
        'balance_to_recover', 'status',
        'document_attachments', 'journal_entry_id',
        'approved_by', 'approved_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'settlement_date'      => 'date',
            'approved_at'          => 'datetime',
            'actual_amount'        => 'decimal:2',
            'advance_paid'         => 'decimal:2',
            'balance_to_recover'   => 'decimal:2',
            'document_attachments' => 'array',
        ];
    }

    public static function statuses(): array
    {
        return ['draft' => 'Draft', 'approved' => 'Approved', 'closed' => 'Closed'];
    }

    public function isDraft(): bool   { return $this->status === 'draft'; }
    public function isClosed(): bool  { return $this->status === 'closed'; }

    public function perdiemRequest(): BelongsTo { return $this->belongsTo(PerdiemRequest::class, 'perdiem_request_id'); }
    public function journalEntry(): BelongsTo   { return $this->belongsTo(JournalEntry::class); }
    public function approvedBy(): BelongsTo     { return $this->belongsTo(User::class, 'approved_by'); }
}
