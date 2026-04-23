<?php

namespace App\Models\Finance;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerdiemType extends Model
{
    protected $table = 'finance_perdiem_types';

    protected $fillable = [
        'code',
        'name',
        'category',
        'default_daily_rate',
        'currency_id',
        'taxable',
        'requires_advance',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_daily_rate' => 'decimal:2',
            'taxable'            => 'boolean',
            'requires_advance'   => 'boolean',
            'is_active'          => 'boolean',
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public static function categories(): array
    {
        return [
            'travel'           => 'Travel',
            'training'         => 'Training',
            'field_work'       => 'Field Work',
            'program_activity' => 'Program Activity',
            'other'            => 'Other',
        ];
    }

    public static function activeOptions(): array
    {
        return static::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function perdiemRequests(): HasMany
    {
        return $this->hasMany(PerdiemRequest::class, 'perdiem_type_id');
    }

    public function taxRules(): HasMany
    {
        return $this->hasMany(PerdiemTaxRule::class, 'perdiem_type_id');
    }
}
