<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileSharingSetting extends Model
{
    protected $table = 'file_sharing_settings';

    protected $fillable = [
        'key',
        'group',
        'label',
        'value',
        'data_type',
        'description',
    ];

    public static function groups(): array
    {
        return [
            'general' => 'General',
            'sharing' => 'Sharing',
            'security' => 'Security',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->data_type) {
            'integer' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $setting->value, true),
            default => $setting->value,
        };
    }

    public static function maxFileSizeMb(): int
    {
        return max(1, (int) static::get('max_file_size_mb', 25));
    }

    public static function defaultLinkExpiryDays(): int
    {
        return max(0, (int) static::get('default_link_expiry_days', 7));
    }

    public static function isPublicSharingEnabled(): bool
    {
        return (bool) static::get('public_sharing_enabled', true);
    }

    public static function requiresPublicPassword(): bool
    {
        return (bool) static::get('require_public_password', false);
    }

    /**
     * @return string[]
     */
    public static function allowedFileExtensions(): array
    {
        $value = static::get('allowed_file_types', [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'txt', 'csv', 'zip',
        ]);

        if (is_string($value)) {
            $value = array_filter(array_map('trim', explode(',', $value)));
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($ext) => strtolower(ltrim((string) $ext, '.')))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
