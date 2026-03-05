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
            $target->scope_location = static::normalizeScope($target->scope_location);
            $target->scope_project = static::normalizeScope($target->scope_project);

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

            $duplicateQuery = static::query()
                ->where('indicator_id', $target->indicator_id)
                ->whereDate('period_start', $periodStart->toDateString())
                ->whereDate('period_end', $periodEnd->toDateString())
                ->when(
                    $target->scope_project === null,
                    fn ($query) => $query->whereNull('scope_project'),
                    fn ($query) => $query->where('scope_project', $target->scope_project)
                )
                ->when(
                    $target->scope_location === null,
                    fn ($query) => $query->whereNull('scope_location'),
                    fn ($query) => $query->where('scope_location', $target->scope_location)
                );

            if ($target->exists) {
                $duplicateQuery->whereKeyNot($target->getKey());
            }

            if ($duplicateQuery->exists()) {
                throw ValidationException::withMessages([
                    'period_start' => 'Duplicate target detected for the same indicator, period, project, and location scope.',
                ]);
            }
        });
    }

    private static function normalizeScope(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(MeIndicator::class, 'indicator_id');
    }
}
