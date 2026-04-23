<?php

declare(strict_types=1);

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeReportingPeriod extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_reporting_periods';

    protected $fillable = [
        'project_id',
        'type',
        'start_date',
        'end_date',
        'label',
        'is_locked',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_locked'  => 'boolean',
        'project_id' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(MeProject::class, 'project_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(MeBeneficiaryFeedback::class, 'reporting_period_id');
    }

    public function getDisplayLabelAttribute(): string
    {
        return "{$this->label} ({$this->type})";
    }
}
