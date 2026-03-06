<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidEvaluation extends Model
{
    protected $table = 'procurement_bid_evaluations';

    protected $fillable = [
        'bid_id', 'evaluator_id', 'stage', 'score', 'comments', 'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'score'        => 'decimal:2',
            'evaluated_at' => 'datetime',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────
    public function bid(): BelongsTo      { return $this->belongsTo(Bid::class); }
    public function evaluator(): BelongsTo { return $this->belongsTo(User::class, 'evaluator_id'); }
}
