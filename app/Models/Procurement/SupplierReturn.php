<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierReturn extends Model
{
    use SoftDeletes;

    protected $table = 'procurement_supplier_returns';

    protected $fillable = [
        'return_number', 'goods_receipt_id', 'purchase_order_id', 'supplier_id',
        'return_date', 'reason', 'return_notes', 'status',
        'expected_resolution_date', 'resolution_notes', 'created_by',
    ];

    protected $casts = [
        'return_date'              => 'date',
        'expected_resolution_date' => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────────
    public function goodsReceipt(): BelongsTo { return $this->belongsTo(GoodsReceipt::class); }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function supplier(): BelongsTo      { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo       { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany           { return $this->hasMany(SupplierReturnItem::class); }

    // ── Auto-generate return number ──────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (self $rtv) {
            if (empty($rtv->return_number)) {
                $year  = now()->format('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $rtv->return_number = sprintf('RTV-%s-%04d', $year, $count);
            }
            $rtv->created_by ??= auth()->id();
        });
    }

    // ── Status helpers ───────────────────────────────────────────────
    public function isDraft(): bool      { return $this->status === 'Draft'; }
    public function isCompleted(): bool  { return $this->status === 'Completed'; }
}
