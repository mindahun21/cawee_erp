<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppraisalSection extends Model
{
    protected $fillable = ['template_id', 'title', 'sort_order'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(AppraisalTemplate::class, 'template_id');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(AppraisalCriterion::class, 'section_id')->orderBy('sort_order');
    }
}
