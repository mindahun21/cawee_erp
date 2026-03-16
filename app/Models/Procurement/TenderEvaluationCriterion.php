<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenderEvaluationCriterion extends Model
{
    protected $table = 'procurement_tender_evaluation_criteria';

    protected $fillable = [
        'tender_id', 'name', 'description', 'weight', 'sort_order',
    ];

    protected $casts = [
        'weight'     => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function tender(): BelongsTo { return $this->belongsTo(Tender::class); }
    public function scores(): HasMany   { return $this->hasMany(BidCriterionScore::class, 'criterion_id'); }
}
