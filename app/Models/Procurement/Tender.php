<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tender extends Model
{
    use SoftDeletes;

    protected $table = 'procurement_tenders';

    protected $fillable = [
        'tender_number', 'requisition_id', 'title', 'description', 'method', 'status',
        'issue_date', 'submission_deadline', 'opening_date', 'award_date',
        'estimated_value', 'currency', 'evaluation_criteria', 'terms_and_conditions',
        'attachments', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date'            => 'date',
            'submission_deadline'   => 'date',
            'opening_date'          => 'date',
            'award_date'            => 'date',
            'estimated_value'       => 'decimal:2',
            'evaluation_criteria'   => 'array',
            'attachments'           => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $t) {
            if (empty($t->tender_number)) {
                $year = now()->format('Y');
                $seq  = static::withTrashed()->whereYear('created_at', $year)->count() + 1;
                $t->tender_number = sprintf('TND-%s-%04d', $year, $seq);
            }
            if (empty($t->created_by)) {
                $t->created_by = auth()->id();
            }
        });
    }

    // ── Relationships ───────────────────────────────────────────────
    public function requisition(): BelongsTo  { return $this->belongsTo(Requisition::class); }
    public function creator(): BelongsTo      { return $this->belongsTo(User::class, 'created_by'); }
    public function bids(): HasMany            { return $this->hasMany(Bid::class); }
    public function evaluationCriteria(): HasMany { return $this->hasMany(TenderEvaluationCriterion::class)->orderBy('sort_order'); }

    // ── Computed ────────────────────────────────────────────────────
    public function isOpen(): bool            { return $this->status === 'Published'; }
    public function isClosedForSubmission(): bool
    {
        return $this->submission_deadline && $this->submission_deadline->isPast();
    }

    public function getAwardedBidAttribute(): ?Bid
    {
        return $this->bids()->where('status', 'Awarded')->first();
    }
}
