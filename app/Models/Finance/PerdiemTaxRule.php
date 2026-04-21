<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerdiemTaxRule extends Model
{
    protected $table = 'finance_perdiem_tax_rules';

    protected $fillable = [
        'perdiem_type_id', 'threshold_amount', 'tax_rate',
        'tax_type', 'effective_date', 'expiry_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'threshold_amount' => 'decimal:2',
            'tax_rate'         => 'decimal:4',
            'effective_date'   => 'date',
            'expiry_date'      => 'date',
        ];
    }

    public static function taxTypes(): array
    {
        return ['income_tax' => 'Income Tax', 'withholding' => 'Withholding Tax', 'none' => 'None'];
    }

    public function perdiemType(): BelongsTo { return $this->belongsTo(PerdiemType::class, 'perdiem_type_id'); }
}
