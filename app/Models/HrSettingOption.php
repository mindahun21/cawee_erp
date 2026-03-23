<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrSettingOption extends Model
{
    protected $table = 'hr_setting_options';

    protected $fillable = [
        'category',
        'code',
        'label',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public const CATEGORIES = [
        'vehicle_type' => 'Vehicle Type',
        'vehicle_status' => 'Vehicle Status (Current Status)',
        'vehicle_service_type' => 'Vehicle Service Type',
        'vehicle_urgency' => 'Vehicle Urgency',
        'maintenance_rule_type' => 'Maintenance Rule Type',
        'service_provider' => 'Service Provider',
        'notification_channel' => 'Notification Channel',
        'reminder_lead_time' => 'Reminder Lead Time',
    ];

    public static function optionsFor(string $category): array
    {
        return static::query()
            ->where('category', $category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->pluck('label', 'id')
            ->toArray();
    }

    public function branches(): HasMany
    {
        return $this->hasMany(HrBranch::class , 'branch_type_option_id');
    }
}
