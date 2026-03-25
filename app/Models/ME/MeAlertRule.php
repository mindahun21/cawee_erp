<?php

namespace App\Models\ME;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeAlertRule extends Model
{
    use HasFactory;

    protected $table = 'me_alert_rules';

    protected $fillable = [
        'indicator_id',
        'name',
        'is_active',
        'condition',
        'rule_type',
        'threshold',
        'warning_threshold',
        'critical_threshold',
        'severity',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'threshold' => 'decimal:2',
        'warning_threshold' => 'decimal:2',
        'critical_threshold' => 'decimal:2',
    ];

    public function indicator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MeIndicator::class, 'indicator_id');
    }
}
