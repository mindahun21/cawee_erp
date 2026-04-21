<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Encumbrance extends Model
{
    protected $table = 'finance_encumbrances';
    protected $fillable = ['commitment_id', 'source_type', 'source_id', 'budget_id', 'budget_line_id', 'amount', 'currency_id', 'encumbrance_date', 'status'];
    protected function casts(): array { return ['encumbrance_date' => 'date', 'amount' => 'decimal:2']; }
    public static function statuses(): array { return ['open' => 'Open', 'partially_liquidated' => 'Partially Liquidated', 'fully_liquidated' => 'Fully Liquidated', 'cancelled' => 'Cancelled']; }

    public function budget(): BelongsTo     { return $this->belongsTo(Budget::class); }
    public function budgetLine(): BelongsTo { return $this->belongsTo(BudgetLine::class); }
    public function commitment(): BelongsTo { return $this->belongsTo(Commitment::class); }
}
