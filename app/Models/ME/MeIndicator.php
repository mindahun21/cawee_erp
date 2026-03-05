<?php

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MeIndicator extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_indicators';

    protected $fillable = [
        'project_id',
        'code',
        'name',
        'framework_type',
        'unit',
        'frequency',
        'description',
        'is_active',
        'disaggregation_required',
        'threshold_warning',
        'threshold_critical',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'is_active' => 'boolean',
        'disaggregation_required' => 'boolean',
        'threshold_warning' => 'decimal:2',
        'threshold_critical' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(MeProject::class, 'project_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(MeIndicatorTarget::class, 'indicator_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(MeIndicatorReport::class, 'indicator_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(MeAlert::class, 'indicator_id');
    }

    public function disaggregationCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            MeDisaggregationCategory::class,
            'me_indicator_disaggregation',
            'indicator_id',
            'category_id'
        );
    }

    public function latestTarget(): HasOne
    {
        return $this->hasOne(MeIndicatorTarget::class, 'indicator_id')->latestOfMany('period_end');
    }

    public function latestReport(): HasOne
    {
        return $this->hasOne(MeIndicatorReport::class, 'indicator_id')->latestOfMany('period_end');
    }
}
