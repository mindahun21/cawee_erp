<?php

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeAlert extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_alerts';

    protected $fillable = [
        'project_id',
        'reporting_period_id',
        'indicator_id',
        'report_id',
        'severity',
        'status',
        'message',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'indicator_id' => 'integer',
        'report_id' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(MeIndicator::class, 'indicator_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(MeIndicatorReport::class, 'report_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(MeProject::class, 'project_id');
    }

    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(MeReportingPeriod::class, 'reporting_period_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'resolved_by');
    }
}
