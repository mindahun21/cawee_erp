<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidCriterionScore extends Model
{
    protected $table = 'procurement_bid_criterion_scores';

    protected $fillable = [
        'bid_id', 'criterion_id', 'score', 'notes', 'scored_by', 'scored_at',
    ];

    protected $casts = [
        'score'     => 'decimal:2',
        'scored_at' => 'datetime',
    ];

    public function bid(): BelongsTo       { return $this->belongsTo(Bid::class); }
    public function criterion(): BelongsTo { return $this->belongsTo(TenderEvaluationCriterion::class, 'criterion_id'); }
    public function scorer(): BelongsTo    { return $this->belongsTo(User::class, 'scored_by'); }
}
