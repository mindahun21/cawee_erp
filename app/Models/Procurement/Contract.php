<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use SoftDeletes;

    protected $table = 'procurement_contracts';

    protected $fillable = [
        'contract_number', 'tender_id', 'bid_id', 'purchase_order_id', 'supplier_id', 'created_by',
        'title', 'description', 'contract_type', 'status',
        'effective_date', 'expiry_date', 'supplier_signed_at', 'org_signed_at',
        'currency', 'contract_value', 'advance_payment_percentage', 'payment_terms',
        'org_signatory_name', 'org_signatory_title', 'supplier_contact_person',
        'approval_status', 'approved_by', 'approved_at', 'approval_remarks',
        'special_conditions', 'attachments',
    ];

    protected function casts(): array
    {
        return [
            'effective_date'           => 'date',
            'expiry_date'              => 'date',
            'supplier_signed_at'       => 'date',
            'org_signed_at'            => 'date',
            'approved_at'              => 'datetime',
            'contract_value'           => 'decimal:2',
            'advance_payment_percentage' => 'decimal:2',
            'attachments'              => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $c) {
            if (empty($c->contract_number)) {
                $year = now()->format('Y');
                $seq  = static::whereYear('created_at', $year)->count() + 1;
                $c->contract_number = sprintf('CTR-%s-%04d', $year, $seq);
            }
            if (empty($c->created_by)) {
                $c->created_by = auth()->id();
            }
        });
    }

    // ── Relationships ───────────────────────────────────────────────
    public function tender(): BelongsTo        { return $this->belongsTo(Tender::class); }
    public function bid(): BelongsTo           { return $this->belongsTo(Bid::class); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function supplier(): BelongsTo      { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo       { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo      { return $this->belongsTo(User::class, 'approved_by'); }
    public function versions(): HasMany        { return $this->hasMany(ContractVersion::class); }

    // ── Computed ─────────────────────────────────────────────────────
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast()
            && !in_array($this->status, ['Terminated', 'Completed', 'Expired']);
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) return null;
        return max(0, now()->diffInDays($this->expiry_date, false));
    }

    public function getLatestVersionAttribute(): ?ContractVersion
    {
        return $this->versions()->orderByDesc('version_number')->first();
    }
}
