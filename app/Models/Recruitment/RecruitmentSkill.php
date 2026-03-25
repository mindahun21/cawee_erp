<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(RecruitmentSkillCategory::class, 'category_id');
    }
}
