<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileSharingSetting extends Model
{
    public const CORE_KEYS = [
        'max_file_size_mb',
        'allowed_file_types',
        'default_link_expiry_days',
        'public_sharing_enabled',
        'require_public_password',
    ];

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

    public static function dataTypes(): array
    {
        return [
            'string' => 'String',
            'integer' => 'Integer',
            'boolean' => 'Boolean',
            'json' => 'List / JSON',
        ];
    }

    public function isCore(): bool
    {
        return in_array($this->key, self::CORE_KEYS, true);
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

    /**
     * @return string[]
     */
    public static function acceptedUploadTypes(): array
    {
        $mimeMap = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'png' => ['image/png'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'txt' => ['text/plain'],
            'csv' => ['text/csv', 'text/plain', 'application/vnd.ms-excel'],
            'zip' => ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'],
        ];

        return collect(static::allowedFileExtensions())
            ->flatMap(function (string $ext) use ($mimeMap): array {
                return array_merge(['.'.$ext], $mimeMap[$ext] ?? []);
            })
            ->unique()
            ->values()
            ->all();
    }
}
