<?php

namespace App\Models\ME;

use App\Models\BRT\BrtProgressUpdate;
use App\Models\BRT\BrtTrainingEvent;
use App\Models\ME\Concerns\LogsMeAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeProject extends Model
{
    use HasFactory;
    use LogsMeAudit;
    use SoftDeletes;

    protected $table = 'me_projects';

    protected $fillable = [
        'name',
        'project_code',
        'description',
        'start_date',
        'end_date',
        'status',
        'project_type',
        'donor',
        'budget',
        'budget_currency',
        'implementing_org',
        'target_beneficiaries',
        'location',
        'manager_id',
    ];

    protected $casts = [
        'start_date'           => 'date',
        'end_date'             => 'date',
        'budget'               => 'decimal:2',
        'target_beneficiaries' => 'integer',
        'manager_id'           => 'integer',
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

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function trainingEvents(): HasMany
    {
        return $this->hasMany(BrtTrainingEvent::class, 'project_id');
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(BrtProgressUpdate::class, 'project_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'    => 'success',
            'planning'  => 'info',
            'on_hold'   => 'warning',
            'completed' => 'primary',
            'cancelled' => 'danger',
            default     => 'gray',
        };
    }
}
