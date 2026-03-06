<?php

namespace App\Models\ME;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeAlertRule extends Model
{
    use HasFactory;

    protected $table = 'me_alert_rules';

    protected $fillable = [
        'name',
        'is_active',
        'condition',
        'warning_threshold',
        'critical_threshold',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'warning_threshold' => 'decimal:2',
        'critical_threshold' => 'decimal:2',
    ];
}
