<?php

namespace App\Models\Recruitment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RecruitmentApprovalRecord extends Model
{
    protected $table = 'recruitment_approval_records';

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'submission_cycle',
        'stage_id',
        'stage_order',
        'stage_name',
        'required_role',
        'status',
        'decided_by',
        'decided_at',
        'notes',
    ];

    protected $casts = [
        'decided_at'       => 'datetime',
        'stage_order'      => 'integer',
        'submission_cycle' => 'integer',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApprovalStage::class, 'stage_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
