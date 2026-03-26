<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class FinanceSetting extends Model
{
    protected $table = 'finance_settings';

    protected $fillable = [
        'key',
        'group',
        'label',
        'value',
        'data_type',
        'description',
    ];

    // ── Static accessor ───────────────────────────────────────────────

    /**
     * Retrieve the typed value for a given key, with an optional default.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->data_type) {
            'integer' => (int) $setting->value,
            'decimal' => (float) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }

    /**
     * Update or create a setting by key.
     */
    public static function set(string $key, mixed $value): void
    {
        static::where('key', $key)->update(['value' => $value]);
    }

    // ── Group helper ──────────────────────────────────────────────────

    public static function groups(): array
    {
        return [
            'general'   => 'General',
            'tax'       => 'Tax',
            'payroll'   => 'Payroll',
            'perdiem'   => 'Per Diem',
            'reporting' => 'Reporting',
        ];
    }
}
