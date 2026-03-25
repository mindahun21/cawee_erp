<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class TaxType extends Model
{
    protected $table = 'finance_tax_types';

    protected $fillable = [
        'code',
        'name',
        'category',
        'default_rate',
        'is_automatic',
        'applies_to_individuals',
        'applies_to_organizations',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_rate'              => 'decimal:4',
            'is_automatic'              => 'boolean',
            'applies_to_individuals'    => 'boolean',
            'applies_to_organizations'  => 'boolean',
            'is_active'                 => 'boolean',
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public static function categories(): array
    {
        return [
            'withholding_tax' => 'Withholding Tax',
            'vat'             => 'VAT',
            'income_tax'      => 'Income Tax',
            'pension'         => 'Pension',
            'other'           => 'Other',
        ];
    }

    public static function activeOptions(): array
    {
        return static::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Returns the rate as a human-readable percentage string.
     */
    public function rateLabel(): string
    {
        return number_format((float) $this->default_rate * 100, 2) . '%';
    }
}
