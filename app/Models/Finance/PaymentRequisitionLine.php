<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequisitionLine extends Model
{
    protected $table = 'finance_payment_requisition_lines';

    protected $fillable = [
        'payment_requisition_id', 'chart_of_account_id',
        'description', 'quantity', 'unit_price', 'line_total',
        'activity_code', 'project_id', 'donor_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity'   => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $line) {
            $line->line_total = (float)$line->quantity * (float)$line->unit_price;
        });
    }

    public function requisition(): BelongsTo { return $this->belongsTo(PaymentRequisition::class, 'payment_requisition_id'); }
    public function account(): BelongsTo     { return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id'); }
}
