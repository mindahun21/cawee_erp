<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppraisalCriterion extends Model
{
    protected $table = 'appraisal_criteria';

    protected $fillable = [
        'section_id', 'factor_name', 'description', 'weight', 'max_score', 'sort_order', 'is_active',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(AppraisalSection::class, 'section_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AppraisalScore::class, 'criterion_id');
    }
}
