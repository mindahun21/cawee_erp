<?php

declare(strict_types=1);

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeBeneficiaryFeedback extends Model
{
    use HasFactory;
    use LogsMeAudit;
    use SoftDeletes;

    protected $table = 'me_beneficiary_feedback';

    protected $fillable = [
        'project_id',
        'reporting_period_id',
        'location_id',
        'gender_option_id',
        'age_group_option_id',
        'disability_option_id',
        'submitted_at',
        'location',
        'sentiment',
        'rating',
        'channel',
        'comment',
        'metadata',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'metadata'     => 'array',
        'rating'       => 'integer',
        'project_id'   => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(MeProject::class, 'project_id');
    }

    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(MeReportingPeriod::class, 'reporting_period_id');
    }

    public function locationRecord(): BelongsTo
    {
        return $this->belongsTo(MeLocation::class, 'location_id');
    }

    public function genderOption(): BelongsTo
    {
        return $this->belongsTo(MeDisaggregationOption::class, 'gender_option_id');
    }

    public function ageGroupOption(): BelongsTo
    {
        return $this->belongsTo(MeDisaggregationOption::class, 'age_group_option_id');
    }

    public function disabilityOption(): BelongsTo
    {
        return $this->belongsTo(MeDisaggregationOption::class, 'disability_option_id');
    }

    // ─── Derived helpers ──────────────────────────────────────────────────────

    public function getSentimentColorAttribute(): string
    {
        return match ($this->sentiment) {
            'positive' => 'success',
            'neutral'  => 'warning',
            'negative' => 'danger',
            default    => 'gray',
        };
    }

    public function getRatingStarsAttribute(): string
    {
        if (! $this->rating) {
            return '—';
        }
        return str_repeat('★', (int) $this->rating) . str_repeat('☆', 5 - (int) $this->rating);
    }
}
