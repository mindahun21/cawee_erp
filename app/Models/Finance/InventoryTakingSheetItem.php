<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTakingSheetItem extends Model
{
    protected $table = 'finance_inventory_taking_sheet_items';

    protected $fillable = [
        'inventory_taking_sheet_id', 'item_type', 'item_id', 'item_description',
        'book_quantity', 'physical_quantity', 'variance', 'unit_cost', 'variance_amount', 'notes'
    ];

    protected function casts(): array
    {
        return [
            'book_quantity' => 'decimal:4',
            'physical_quantity' => 'decimal:4',
            'variance' => 'decimal:4',
            'unit_cost' => 'decimal:2',
            'variance_amount' => 'decimal:2',
        ];
    }

    protected static function booted()
    {
        static::saving(function ($item) {
            $item->variance = (float)$item->physical_quantity - (float)$item->book_quantity;
            $item->variance_amount = $item->variance * (float)$item->unit_cost;
        });
    }

    public function sheet(): BelongsTo { return $this->belongsTo(InventoryTakingSheet::class, 'inventory_taking_sheet_id'); }
}
