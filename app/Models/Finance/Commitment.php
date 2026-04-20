<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commitment extends Model
{
    protected $table = 'finance_commitments';
    protected $fillable = ['source_type', 'source_id', 'budget_id', 'budget_line_id', 'amount', 'currency_id', 'commitment_date', 'status', 'notes'];
    protected function casts(): array { return ['commitment_date' => 'date', 'amount' => 'decimal:2']; }
    public static function statuses(): array { return ['open' => 'Open', 'partially_utilized' => 'Partially Utilized', 'fully_utilized' => 'Fully Utilized', 'cancelled' => 'Cancelled']; }

    public function budget(): BelongsTo     { return $this->belongsTo(Budget::class); }
    public function budgetLine(): BelongsTo { return $this->belongsTo(BudgetLine::class); }
}
