<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bid extends Model
{
    use SoftDeletes;

    protected $table = 'procurement_bids';

    protected $fillable = [
        'tender_id', 'supplier_id', 'reference_number', 'submission_date',
        'bid_amount', 'currency', 'delivery_days', 'status',
        'technical_score', 'financial_score', 'composite_score',
        'validity_date', 'notes', 'attachments', 'conflict_of_interest_declared',
    ];

    protected function casts(): array
    {
        return [
            'submission_date'               => 'date',
            'validity_date'                 => 'date',
            'bid_amount'                    => 'decimal:2',
            'technical_score'              => 'decimal:2',
            'financial_score'              => 'decimal:2',
            'composite_score'              => 'decimal:2',
            'conflict_of_interest_declared' => 'boolean',
            'attachments'                   => 'array',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────
    public function tender(): BelongsTo      { return $this->belongsTo(Tender::class); }
    public function supplier(): BelongsTo    { return $this->belongsTo(Supplier::class); }
    public function evaluations(): HasMany   { return $this->hasMany(BidEvaluation::class); }
    public function purchaseOrder(): ?PurchaseOrder { return $this->hasOne(PurchaseOrder::class)->first(); }
}
