<?php

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use App\Observers\ME\MeIndicatorReportObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[ObservedBy([MeIndicatorReportObserver::class])]
class MeIndicatorReport extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_indicator_reports';

    protected $fillable = [
        'indicator_id',
        'reporting_period_id',
        'period_start',
        'period_end',
        'actual_value',
        'actual_text',
        'scope_location',
        'scope_project',
        'source',
        'entered_by',
        'entered_at',
        'notes',
        'comment',
    ];

    protected $casts = [
        'indicator_id' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
        'actual_value' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (MeIndicatorReport $report): void {
            $periodStart = Carbon::parse($report->period_start);
            $periodEnd = Carbon::parse($report->period_end);

            if ($periodEnd->lt($periodStart)) {
                throw ValidationException::withMessages([
                    'period_end' => 'The period end date must be on or after period start.',
                ]);
            }

            if ((float) $report->actual_value < 0) {
                throw ValidationException::withMessages([
                    'actual_value' => 'The actual value must be 0 or greater.',
                ]);
            }
        });
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(MeIndicator::class, 'indicator_id');
    }

    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(MeReportingPeriod::class, 'reporting_period_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'entered_by');
    }

    public function disaggregationValues(): HasMany
    {
        return $this->hasMany(MeReportDisaggregationValue::class, 'report_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(MeAlert::class, 'report_id');
    }

    public function resolvedTarget(): ?MeIndicatorTarget
    {
        return MeIndicatorTarget::query()
            ->where('indicator_id', $this->indicator_id)
            ->whereDate('period_start', '<=', $this->period_end)
            ->whereDate('period_end', '>=', $this->period_start)
            ->where(function ($query): void {
                if ($this->scope_location === null || $this->scope_location === '') {
                    $query->whereNull('scope_location');

                    return;
                }

                $query->whereNull('scope_location')
                    ->orWhere('scope_location', $this->scope_location);
            })
            ->where(function ($query): void {
                if ($this->scope_project === null || $this->scope_project === '') {
                    $query->whereNull('scope_project');

                    return;
                }

                $query->whereNull('scope_project')
                    ->orWhere('scope_project', $this->scope_project);
            })
            ->orderByRaw('CASE WHEN scope_location IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN scope_project IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('period_end')
            ->first();
    }

    public function resolvedTargetValue(): float
    {
        return (float) ($this->resolvedTarget()?->target_value ?? 0);
    }

    public function progressPercent(): float
    {
        $target = $this->resolvedTargetValue();

        if ($target <= 0) {
            return 0.0;
        }

        return round(((float) $this->actual_value / $target) * 100, 2);
    }

    public function progressStatus(): string
    {
        $progress = $this->progressPercent();

        if ($progress >= 90) {
            return 'on_track';
        }

        if ($progress >= 70) {
            return 'needs_attention';
        }

        return 'off_track';
    }
}
