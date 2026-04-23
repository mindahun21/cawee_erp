<?php

namespace App\Models\Finance;

use App\Models\Donor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostBuildup extends Model
{
    protected $table = 'finance_cost_buildups';

    protected $fillable = [
        'reference', 'budget_id', 'budget_line_id', 'account_id', 'transaction_date',
        'description', 'amount', 'currency_id', 'exchange_rate_to_base', 'activity_code',
        'project_id', 'cost_center_id', 'donor_id', 'prepared_by'
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'exchange_rate_to_base' => 'decimal:6',
        ];
    }

    public function budget(): BelongsTo { return $this->belongsTo(Budget::class); }
    public function budgetLine(): BelongsTo { return $this->belongsTo(BudgetLine::class); }
    public function account(): BelongsTo { return $this->belongsTo(ChartOfAccount::class, 'account_id'); }
    public function currency(): BelongsTo { return $this->belongsTo(\App\Models\Currency::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function costCenter(): BelongsTo { return $this->belongsTo(CostCenter::class); }
    public function donor(): BelongsTo { return $this->belongsTo(Donor::class); }
    public function preparedBy(): BelongsTo { return $this->belongsTo(User::class, 'prepared_by'); }
}
