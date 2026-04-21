<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetLine extends Model
{
    protected $table = 'finance_budget_lines';

    protected $fillable = [
        'budget_id', 'account_id', 'activity_code', 'activity_description',
        'q1_amount', 'q2_amount', 'q3_amount', 'q4_amount', 'total_budgeted',
        'committed', 'encumbered', 'actual',
    ];

    protected function casts(): array
    {
        return [
            'q1_amount'     => 'decimal:2',
            'q2_amount'     => 'decimal:2',
            'q3_amount'     => 'decimal:2',
            'q4_amount'     => 'decimal:2',
            'total_budgeted' => 'decimal:2',
            'committed'     => 'decimal:2',
            'encumbered'    => 'decimal:2',
            'actual'        => 'decimal:2',
        ];
    }

    public function remaining(): float
    {
        return (float)$this->total_budgeted - (float)$this->committed - (float)$this->encumbered - (float)$this->actual;
    }

    public function budget(): BelongsTo  { return $this->belongsTo(Budget::class); }
    public function account(): BelongsTo { return $this->belongsTo(ChartOfAccount::class, 'account_id'); }
}

