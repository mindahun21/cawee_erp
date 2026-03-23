<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_procurement_default',
    ];

    protected function casts(): array
    {
        return [
            'is_procurement_default' => 'boolean',
            'exchange_rate' => 'decimal:6',
        ];
    }

    /**
     * Returns the symbol for a given currency code. Fallback: returns the code itself.
     * Used by all procurement form money-field prefixes for live reactive display.
     */
    public static function symbolFor(?string $code): string
    {
        if (! $code) {
            return static::where('is_procurement_default', true)->value('symbol') ?? 'Br';
        }
        return static::where('code', $code)->value('symbol') ?? $code;
    }

    protected static function booted(): void
    {
        // Enforce a single procurement default: when one currency is marked as
        // default (via the form toggle OR the "Set as Default" action), all
        // other currencies automatically lose their default status.
        static::saving(function (self $currency) {
            if ($currency->is_procurement_default && $currency->isDirty('is_procurement_default')) {
                static::query()
                    ->where('id', '!=', $currency->id)
                    ->where('is_procurement_default', true)
                    ->update(['is_procurement_default' => false]);
            }
        });
    }

    /**
     * Returns the code of the default procurement currency (e.g. 'ETB').
     * Falls back to 'ETB' when no default has been configured yet.
     */
    public static function procurementDefault(): string
    {
        return static::where('is_procurement_default', true)->value('code') ?? 'ETB';
    }

    /**
     * Returns an associative [code => label] array for use in Select components.
     */
    public static function procurementOptions(): array
    {
        return static::orderBy('code')
            ->pluck('name', 'code')
            ->map(fn ($name, $code) => "{$code} — {$name}")
            ->toArray();
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}
