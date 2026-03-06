<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionItem extends Model
{
    protected $table = 'procurement_requisition_items';

    protected $fillable = [
        'requisition_id', 'description', 'unit', 'quantity',
        'estimated_unit_price', 'estimated_total', 'specifications',
    ];

    protected function casts(): array
    {
        return [
            'quantity'              => 'decimal:4',
            'estimated_unit_price'  => 'decimal:2',
            'estimated_total'       => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->estimated_total = round(
                (float)$item->quantity * (float)$item->estimated_unit_price,
                2
            );
        });
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }
}
