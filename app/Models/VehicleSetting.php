<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleSetting extends Model
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
        'service_provider' => 'Service Provider',
        'agreement_payment_cycle' => 'Agreement Payment Cycle',
        'renewal_decision' => 'Renewal Decision',
        'utility_type' => 'Utility Type',
        'utility_payment_cycle' => 'Utility Payment Cycle',
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
}
