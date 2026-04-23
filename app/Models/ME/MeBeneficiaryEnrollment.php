<?php

declare(strict_types=1);

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeBeneficiaryEnrollment extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_beneficiary_enrollments';

    protected $fillable = [
        'beneficiary_id',
        'project_id',
        'reporting_period_id',
        'enrollment_date',
        'exit_date',
        'participation_status',
        'exit_reason',
        'notes',
    ];

    protected $casts = [
        'enrollment_date'  => 'date',
        'exit_date'        => 'date',
        'beneficiary_id'   => 'integer',
        'project_id'       => 'integer',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(MeBeneficiary::class, 'beneficiary_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(MeProject::class, 'project_id');
    }

    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(MeReportingPeriod::class, 'reporting_period_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->participation_status) {
            'active'      => 'success',
            'completed'   => 'info',
            'enrolled'    => 'primary',
            'dropped_out' => 'danger',
            'suspended'   => 'warning',
            default       => 'gray',
        };
    }
}
