<?php

namespace App\Models\Recruitment;

use App\Contracts\Recruitment\Approvable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecruitmentInterviewSchedule extends Model implements Approvable
{
    use SoftDeletes;

    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'campaign_id',
        'name',
        'round',
        'interview_date',
        'from_time',
        'to_time',
        'location',
        'evaluation_template_id',
        'interview_type',
        'status',
        'approval_workflow_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'interview_date' => 'date',
        // We use string or custom datetime casts for times in Filament if needed, 
        // but for general use 'datetime' is fine.
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCampaign::class, 'campaign_id');
    }

    public function evaluationTemplate(): BelongsTo
    {
        return $this->belongsTo(RecruitmentEvaluationFormTemplate::class, 'evaluation_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvalWorkflow(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function scheduleInterviewers(): HasMany
    {
        return $this->hasMany(RecruitmentScheduleInterviewer::class, 'schedule_id');
    }

    public function interviewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'recruitment_schedule_interviewers', 'schedule_id', 'user_id')
            ->withPivot('role', 'notes');
    }

    public function scheduleCandidates(): HasMany
    {
        return $this->hasMany(RecruitmentScheduleCandidate::class, 'schedule_id');
    }

    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(RecruitmentCandidate::class, 'recruitment_schedule_candidates', 'schedule_id', 'candidate_id')
            ->withPivot(['id', 'candidate_from_time', 'candidate_to_time']);
    }

    // Approvable implementation
    public function approvalDocumentType(): string
    {
        return 'recruitment_interview_schedule';
    }

    public function submittedStatus(): string
    {
        return self::STATUS_SUBMITTED;
    }

    public function draftStatus(): string
    {
        return self::STATUS_DRAFT;
    }

    public function approvedStatus(): string
    {
        return self::STATUS_SCHEDULED;
    }

    public function rejectedStatus(): string
    {
        return self::STATUS_REJECTED;
    }

    public function onFullyApproved(): void
    {
        $this->update(['status' => self::STATUS_SCHEDULED]);
        
        foreach ($this->candidates as $candidate) {
            $candidate->applications()
                ->where('campaign_id', $this->campaign_id)
                ->where('status', 'shortlisted')
                ->update(['status' => 'interview_scheduled']);
        }
    }

    public function onRejected(): void
    {
        $this->update(['status' => self::STATUS_REJECTED]);
    }
}
