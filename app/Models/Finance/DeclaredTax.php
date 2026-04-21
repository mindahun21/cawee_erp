<?php

namespace App\Models\Finance;

use App\Models\Currency;
use App\Models\Donor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeclaredTax extends Model
{
    protected $table = 'finance_declared_taxes';

    protected $fillable = [
        'tax_type_id', 'declaration_period', 'declaration_date',
        'total_income', 'taxable_income', 'tax_payable', 'paid_amount',
        'payment_date', 'reference_number', 'status', 'document_attachment',
    ];

    protected function casts(): array
    {
        return [
            'declaration_date' => 'date',
            'payment_date'     => 'date',
            'total_income'     => 'decimal:2',
            'taxable_income'   => 'decimal:2',
            'tax_payable'      => 'decimal:2',
            'paid_amount'      => 'decimal:2',
        ];
    }

    public static function statuses(): array
    {
        return ['draft' => 'Draft', 'filed' => 'Filed', 'paid' => 'Paid'];
    }

    public function taxType(): BelongsTo { return $this->belongsTo(TaxType::class); }
}
