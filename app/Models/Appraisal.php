<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appraisal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'template_id', 'employee_id', 'evaluator_id', 'project_id',
        'period_start', 'period_end', 'review_date',
        'cumulative_average', 'general_comments', 'status',
        'supervisor_approved_by', 'supervisor_approved_at',
        'hr_approved_by', 'hr_approved_at',
        'director_approved_by', 'director_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start'           => 'date',
            'period_end'             => 'date',
            'review_date'            => 'date',
            'supervisor_approved_at' => 'datetime',
            'hr_approved_at'         => 'datetime',
            'director_approved_at'   => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AppraisalTemplate::class, 'template_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'evaluator_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AppraisalScore::class);
    }

    // Recompute cumulative_average from all scores before saving
    protected static function booted(): void
    {
        static::saved(function (self $appraisal) {
            $scores = $appraisal->scores()->with('criterion')->get();
            if ($scores->isEmpty()) {
                return;
            }
            $totalWeightedScore = 0;
            $totalWeight        = 0;
            foreach ($scores as $score) {
                $weight              = (float) ($score->criterion->weight ?? 1);
                $totalWeightedScore += (float) $score->score * $weight;
                $totalWeight        += $weight;
            }
            $average = $totalWeight > 0 ? round($totalWeightedScore / $totalWeight, 2) : null;

            // Avoid infinite loop
            static::withoutEvents(function () use ($appraisal, $average) {
                $appraisal->update(['cumulative_average' => $average]);
            });
        });
    }
}
