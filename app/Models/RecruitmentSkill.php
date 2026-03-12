<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecruitmentSkill extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function skill(): BelongsTo
    {
        return $this->belongsTo(RecruitmentSkill::class, 'recruitment_skill_id');
    }

    public function companies()
    {
        return $this->belongsToMany(RecruitmentCompany::class);
    }

    public function positions()
    {
        return $this->belongsToMany(
            RecruitmentPosition::class,
            'recruitment_position_skill'
        );
    }
}
