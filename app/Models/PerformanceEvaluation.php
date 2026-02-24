<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceEvaluation extends Model
{
    protected $fillable = [
        'employee_id',
        'evaluator_id',
        'project_id',
        'review_period_start',
        'review_period_end',
        'effort_initiative',
        'technical_competence',
        'teamwork',
        'dependability',
        'planning_organizing',
        'quality_quantity',
        'priority_setting',
        'compliance',
        'written_communication',
        'coordination_collaboration',
        'cumulative_average',
        'general_comments',
        'evaluation_date',
    ];

    protected $casts = [
        'review_period_start'    => 'date',
        'review_period_end'      => 'date',
        'evaluation_date'        => 'date',
        'cumulative_average'     => 'decimal:2',
    ];

    // Automatically recompute average before saving
    protected static function booted(): void
    {
        static::saving(function (self $evaluation) {
            $criteria = [
                'effort_initiative',
                'technical_competence',
                'teamwork',
                'dependability',
                'planning_organizing',
                'quality_quantity',
                'priority_setting',
                'compliance',
                'written_communication',
                'coordination_collaboration',
            ];

            $total = array_sum(array_map(fn ($c) => $evaluation->{$c}, $criteria));
            $evaluation->cumulative_average = round($total / count($criteria), 2);
        });
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
        return $this->belongsTo(Project::class, 'project_id');
    }
}
