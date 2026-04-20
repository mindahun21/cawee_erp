<?php

namespace App\Models\Finance;

use App\Models\Currency;
use App\Models\Donor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialStatement extends Model
{
    protected $table = 'finance_financial_statements';

    protected $fillable = [
        'reference', 'statement_type', 'title',
        'accounting_period_id', 'fiscal_year', 'as_of_date',
        'donor_id', 'project_id', 'cost_center_id',
        'parameters', 'status', 'prepared_by', 'approved_by', 'approved_at', 'file_path',
    ];

    protected function casts(): array
    {
        return [
            'as_of_date'   => 'date',
            'approved_at'  => 'datetime',
            'parameters'   => 'array',
        ];
    }

    public static function types(): array
    {
        return [
            'trial_balance'    => 'Trial Balance',
            'income_statement' => 'Income Statement (P&L)',
            'balance_sheet'    => 'Balance Sheet',
            'cash_flow'        => 'Cash Flow Statement',
            'budget_vs_actual' => 'Budget vs. Actual',
            'donor_expenditure'=> 'Donor Expenditure Report',
            'tax_summary'      => 'Tax Summary',
            'payroll_summary'  => 'Payroll Summary Report',
        ];
    }

    public static function statuses(): array
    {
        return ['draft' => 'Draft', 'finalized' => 'Finalized', 'submitted' => 'Submitted'];
    }

    public function accountingPeriod(): BelongsTo { return $this->belongsTo(AccountingPeriod::class); }
    public function donor(): BelongsTo            { return $this->belongsTo(Donor::class); }
    public function project(): BelongsTo          { return $this->belongsTo(Project::class); }
    public function costCenter(): BelongsTo       { return $this->belongsTo(CostCenter::class); }
    public function preparedBy(): BelongsTo       { return $this->belongsTo(User::class, 'prepared_by'); }
    public function approvedBy(): BelongsTo       { return $this->belongsTo(User::class, 'approved_by'); }
}
