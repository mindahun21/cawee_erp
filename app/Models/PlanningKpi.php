<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanningKpi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'planning_kpis';

    protected $fillable = [
        'plan_id',
        'indicator_name',
        'target_value',
        'actual_value',
        'unit',
        'department_id',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function getVarianceAttribute(): float
    {
        return (float)$this->actual_value - (float)$this->target_value;
    }

    public function getPerformancePercentageAttribute(): float
    {
        if ($this->target_value == 0) return 0;
        return round(((float)$this->actual_value / (float)$this->target_value) * 100, 2);
    }
}
