<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GoodsReceipt extends Model
{
    use SoftDeletes;

    protected $table = 'procurement_goods_receipts';

    protected $fillable = [
        'grn_number', 'purchase_order_id', 'received_by', 'receipt_date',
        'delivery_location', 'delivery_note_number', 'overall_condition',
        'status', 'inspection_notes', 'inspected_by', 'inspected_at',
        'approved_by', 'approved_at', 'attachments',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date'  => 'date',
            'inspected_at'  => 'datetime',
            'approved_at'   => 'datetime',
            'attachments'   => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $gr) {
            if (empty($gr->grn_number)) {
                $year = now()->format('Y');
                $seq  = static::whereYear('created_at', $year)->count() + 1;
                $gr->grn_number = sprintf('GRN-%s-%04d', $year, $seq);
            }
        });
    }

    // ── Relationships ───────────────────────────────────────────────
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function receiver(): BelongsTo      { return $this->belongsTo(User::class, 'received_by'); }
    public function inspector(): BelongsTo     { return $this->belongsTo(User::class, 'inspected_by'); }
    public function approver(): BelongsTo      { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany           { return $this->hasMany(GoodsReceiptItem::class); }
    public function threeWayMatch(): HasOne    { return $this->hasOne(ThreeWayMatch::class); }

    // ── Predicates ──────────────────────────────────────────────────
    public function canInspect(): bool  { return $this->status === 'Draft'; }
    public function canApprove(): bool  { return $this->status === 'Inspecting'; }
    public function isAccepted(): bool  { return in_array($this->status, ['Accepted', 'Partial']); }
}
