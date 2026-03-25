<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentApprovalStage extends Model
{
    protected $table = 'recruitment_approval_stages';

    protected $fillable = [
        'workflow_id',
        'stage_name',
        'stage_order',
        'required_role',
        'can_reject',
    ];

    protected $casts = [
        'can_reject' => 'boolean',
        'stage_order' => 'integer',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApprovalWorkflow::class, 'workflow_id');
    }
}
