<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use SoftDeletes;

    protected $table = 'procurement_payments';

    protected $fillable = [
        'payment_reference', 'invoice_id', 'supplier_id', 'created_by',
        'amount', 'currency', 'payment_method', 'bank_name', 'bank_reference',
        'payment_date', 'scheduled_date', 'status', 'notes',
        // Approval
        'finance_status', 'finance_approved_by', 'finance_approved_at',
        'director_status', 'director_approved_by', 'director_approved_at',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'amount'               => 'decimal:2',
            'payment_date'         => 'date',
            'scheduled_date'       => 'date',
            'finance_approved_at'  => 'datetime',
            'director_approved_at' => 'datetime',
            'attachments'          => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            if (empty($p->payment_reference)) {
                $year = now()->format('Y');
                $seq  = static::withTrashed()->whereYear('created_at', $year)->count() + 1;
                $p->payment_reference = sprintf('PAY-%s-%04d', $year, $seq);
            }
            if (empty($p->created_by)) {
                $p->created_by = auth()->id();
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────────
    public function invoice(): BelongsTo         { return $this->belongsTo(Invoice::class); }
    public function supplier(): BelongsTo        { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo         { return $this->belongsTo(User::class, 'created_by'); }
    public function financeApprover(): BelongsTo { return $this->belongsTo(User::class, 'finance_approved_by'); }
    public function directorApprover(): BelongsTo { return $this->belongsTo(User::class, 'director_approved_by'); }

    // ── Stage predicates ─────────────────────────────────────────────
    public function canFinanceApprove(): bool
    {
        return $this->status === 'Pending Approval' && $this->finance_status === 'Pending';
    }

    public function canDirectorApprove(): bool
    {
        return $this->finance_status === 'Approved' && $this->director_status === 'Pending';
    }

    public function isFullyApproved(): bool
    {
        return $this->director_status === 'Approved';
    }

    public function getCurrentStageAttribute(): string
    {
        if ($this->status === 'Processed') return 'Processed ✓';
        if ($this->status === 'Cancelled') return 'Cancelled';
        if ($this->finance_status === 'Pending') return 'Awaiting Finance';
        if ($this->director_status === 'Pending') return 'Awaiting Director';
        if ($this->isFullyApproved()) return 'Ready to Process';
        return $this->status;
    }
}
