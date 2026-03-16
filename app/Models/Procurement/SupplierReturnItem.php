<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierReturnItem extends Model
{
    protected $table = 'procurement_supplier_return_items';

    protected $fillable = [
        'supplier_return_id', 'grn_item_id', 'description',
        'quantity_returned', 'unit', 'reason', 'notes',
    ];

    public function supplierReturn(): BelongsTo { return $this->belongsTo(SupplierReturn::class); }
    public function grnItem(): BelongsTo        { return $this->belongsTo(GoodsReceiptItem::class, 'grn_item_id'); }
}
