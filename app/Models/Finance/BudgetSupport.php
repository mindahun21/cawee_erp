<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetRevision extends Model
{
    protected $table = 'finance_budget_revisions';
    protected $fillable = ['budget_id', 'revision_number', 'revision_date', 'reason', 'old_total', 'new_total', 'revised_by', 'approved_by'];
    protected function casts(): array { return ['revision_date' => 'date', 'old_total' => 'decimal:2', 'new_total' => 'decimal:2']; }

    public function budget(): BelongsTo    { return $this->belongsTo(Budget::class); }
    public function revisedBy(): BelongsTo { return $this->belongsTo(User::class, 'revised_by'); }
    public function approvedBy(): BelongsTo{ return $this->belongsTo(User::class, 'approved_by'); }
}

class Commitment extends Model
{
    protected $table = 'finance_commitments';
    protected $fillable = ['source_type', 'source_id', 'budget_id', 'budget_line_id', 'amount', 'currency_id', 'commitment_date', 'status', 'notes'];
    protected function casts(): array { return ['commitment_date' => 'date', 'amount' => 'decimal:2']; }
    public static function statuses(): array { return ['open' => 'Open', 'partially_utilized' => 'Partially Utilized', 'fully_utilized' => 'Fully Utilized', 'cancelled' => 'Cancelled']; }

    public function budget(): BelongsTo     { return $this->belongsTo(Budget::class); }
    public function budgetLine(): BelongsTo { return $this->belongsTo(BudgetLine::class); }
}

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
