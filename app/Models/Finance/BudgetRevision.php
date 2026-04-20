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
