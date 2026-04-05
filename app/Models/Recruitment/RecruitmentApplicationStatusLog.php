<?php

namespace App\Models\Recruitment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentApplicationStatusLog extends Model
{
    protected $table = 'recruitment_application_status_logs';

    protected $fillable = [
        'application_id',
        'from_status',
        'to_status',
        'changed_by',
        'reason',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class, 'application_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
