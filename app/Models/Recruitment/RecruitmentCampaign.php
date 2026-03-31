<?php

namespace App\Models\Recruitment;

use App\Models\JobPosition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Contracts\Recruitment\Approvable;
use App\Models\Recruitment\RecruitmentApprovalWorkflow;
use App\Models\Recruitment\RecruitmentApprovalRecord;
use App\Observers\Recruitment\RecruitmentCampaignObserver;

#[ObservedBy(RecruitmentCampaignObserver::class)]
class RecruitmentCampaign extends Model implements Approvable
{
    use HasFactory, SoftDeletes;

    protected $table = 'recruitment_campaigns';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'submission_date' => 'date',
            'is_public'  => 'boolean',
            'display_salary' => 'boolean',
        ];
    }

    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_CLOSED = 'closed';

    protected static function booted()
    {
        static::creating(function ($campaign) {
            if (empty($campaign->campaign_code)) {
                $last = static::orderBy('id', 'desc')->first();
                $next = $last ? $last->id + 1 : 1;
                $campaign->campaign_code = 'CP_' . str_pad($next, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(RecruitmentChannel::class, 'channel_id');
    }

    public function recruitmentPlan(): BelongsTo
    {
        return $this->belongsTo(RecruitmentPlan::class, 'recruitment_plan_id');
    }

    public function approvalWorkflow(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function approvalRecords(): MorphMany
    {
        return $this->morphMany(RecruitmentApprovalRecord::class, 'approvable');
    }

    /* ── Approvable Interface ── */

    public function approvalDocumentType(): string
    {
        return 'recruitment_campaign';
    }

    public function approvedStatus(): string
    {
        return self::STATUS_ACTIVE;
    }

    public function rejectedStatus(): string
    {
        return self::STATUS_REJECTED;
    }

    public function submittedStatus(): string
    {
        return self::STATUS_SUBMITTED;
    }

    public function draftStatus(): string
    {
        return self::STATUS_DRAFT;
    }

    public function onFullyApproved(): void
    {
        $this->update(['status' => $this->approvedStatus()]);
    }

    public function onRejected(): void
    {
        $this->update(['status' => $this->rejectedStatus()]);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED]);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'recruitment_campaign_followers', 'campaign_id', 'user_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(RecruitmentSkill::class, 'recruitment_campaign_skill', 'campaign_id', 'recruitment_skill_id')
                    ->using(RecruitmentCampaignSkill::class)
                    ->withPivot(['is_required', 'min_proficiency'])
                    ->withTimestamps();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(RecruitmentApplication::class, 'campaign_id');
    }
}
