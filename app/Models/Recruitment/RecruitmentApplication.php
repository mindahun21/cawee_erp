<?php

namespace App\Models\Recruitment;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\Recruitment\RecruitmentApplicationObserver;

#[ObservedBy(RecruitmentApplicationObserver::class)]
class RecruitmentApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recruitment_applications';
    protected $guarded = [];

    const STATUS_APPLIED = 'applied';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_SHORTLISTED = 'shortlisted';
    const STATUS_INTERVIEW_SCHEDULED = 'interview_scheduled';
    const STATUS_OFFER_PENDING = 'offer_pending';
    const STATUS_OFFER_ACCEPTED = 'offer_accepted';
    const STATUS_OFFER_DECLINED = 'offer_declined';
    const STATUS_HIRED = 'hired';
    const STATUS_REJECTED = 'rejected';
    const STATUS_WITHDRAWN = 'withdrawn';

    protected $casts = [
        'applied_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCandidate::class, 'candidate_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCampaign::class, 'campaign_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(RecruitmentChannel::class, 'channel_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function shortlister(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shortlisted_by');
    }
}
