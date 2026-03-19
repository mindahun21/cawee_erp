<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class RecruitmentInterview extends Model
{
    protected $table = 'recruitment_interviews';

    protected $fillable = [
        'application_id',
        'type',
        'round',
        'scheduled_at',
        'duration_minutes',
        'location',
        'status',
        'scheduled_by',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function application(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class);
    }

    public function scheduler(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'scheduled_by');
    }

    public function panelists(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'recruitment_interview_panelists', 'interview_id', 'user_id')
                    ->withPivot('role');
    }

    public function evaluationForms(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RecruitmentEvaluationForm::class, 'interview_id');
    }
}
