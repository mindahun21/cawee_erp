<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrefixSetting extends Model
{
    protected $fillable = [
        'key',
        'display_name',
        'prefix',
        'next_number',
    ];

    public static function generateNextCode(string $key): string
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return '';
        }

        $code = ($setting->prefix ?? '') . $setting->next_number;

        $setting->increment('next_number');

        return $code;
    }

    public static function getPrefix(string $key): string
    {
        return self::where('key', $key)->value('prefix') ?? '';
    }

    public static function updateNextNumberFromCode(string $key, string $code): void
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) return;

        $prefix = $setting->prefix ?? '';
        if ($prefix && str_starts_with($code, $prefix)) {
            $numberPart = substr($code, strlen($prefix));
        } else {
            // Fallback: try to extract the numeric part at the end
            preg_match('/(\d+)$/', $code, $matches);
            $numberPart = $matches[1] ?? null;
        }

        if (is_numeric($numberPart)) {
            $usedNumber = (int) $numberPart;
            if ($usedNumber >= $setting->next_number) {
                $setting->update(['next_number' => $usedNumber + 1]);
            }
        }
    }
}
