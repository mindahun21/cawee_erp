<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetCode extends Model
{
    use SoftDeletes;

    protected $table = 'finance_budget_codes';

    protected $fillable = [
        'code',
        'description',
        'cost_category',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
