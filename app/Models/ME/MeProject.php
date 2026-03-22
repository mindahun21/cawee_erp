<?php

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeProject extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_projects';

    protected $fillable = [
        'name',
        'project_code',
        'description',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function indicators(): HasMany
    {
        return $this->hasMany(MeIndicator::class, 'project_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(MeBeneficiaryFeedback::class, 'project_id');
    }

    public function reportingPeriods(): HasMany
    {
        return $this->hasMany(MeReportingPeriod::class, 'project_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(MeBeneficiaryEnrollment::class, 'project_id');
    }

    public function baselineAssessments(): HasMany
    {
        return $this->hasMany(MeBaselineAssessment::class, 'project_id');
    }
}
