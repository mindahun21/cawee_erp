<?php

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class MeSurvey extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_surveys';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'title',
        'period_start',
        'period_end',
        'is_active',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (MeSurvey $survey): void {
            $periodStart = Carbon::parse($survey->period_start);
            $periodEnd = Carbon::parse($survey->period_end);

            if ($periodEnd->lt($periodStart)) {
                throw ValidationException::withMessages([
                    'period_end' => 'The period end date must be on or after period start.',
                ]);
            }
        });
    }

    public function questions(): HasMany
    {
        return $this->hasMany(MeSurveyQuestion::class, 'survey_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(MeSurveyResponse::class, 'survey_id');
    }
}
