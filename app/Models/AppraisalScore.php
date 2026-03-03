<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppraisalScore extends Model
{
    protected $fillable = ['appraisal_id', 'criterion_id', 'score', 'comments'];

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(AppraisalCriterion::class, 'criterion_id');
    }
}
