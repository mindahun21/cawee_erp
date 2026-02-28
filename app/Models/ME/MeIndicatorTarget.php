<?php

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class MeIndicatorTarget extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_indicator_targets';

    protected $fillable = [
        'indicator_id',
        'period_start',
        'period_end',
        'target_value',
        'scope_location',
        'scope_project',
    ];

    protected $casts = [
        'indicator_id' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
        'target_value' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (MeIndicatorTarget $target): void {
            $periodStart = Carbon::parse($target->period_start);
            $periodEnd = Carbon::parse($target->period_end);

            if ($periodEnd->lt($periodStart)) {
                throw ValidationException::withMessages([
                    'period_end' => 'The period end date must be on or after period start.',
                ]);
            }

            if ((float) $target->target_value <= 0) {
                throw ValidationException::withMessages([
                    'target_value' => 'The target value must be greater than 0.',
                ]);
            }
        });
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(MeIndicator::class, 'indicator_id');
    }
}
