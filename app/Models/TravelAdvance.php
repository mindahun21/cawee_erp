<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TravelAdvance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'project_id', 'payment_center', 'place_of_travel', 'purpose',
        'depart_date', 'return_date', 'planned_days', 'per_diem_rate',
        'per_diem_amount', 'accommodation_amount', 'transport_amount',
        'other_amount', 'other_description', 'total_amount',
        'budget_code', 'budget_title', 'status',
        'checked_by', 'checked_at', 'approved_by', 'approved_at',
        'authorized_by', 'authorized_at',
    ];

    protected function casts(): array
    {
        return [
            'depart_date'   => 'date',
            'return_date'   => 'date',
            'checked_at'    => 'datetime',
            'approved_at'   => 'datetime',
            'authorized_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $ta) {
            $ta->total_amount = (float) $ta->per_diem_amount
                + (float) $ta->accommodation_amount
                + (float) $ta->transport_amount
                + (float) $ta->other_amount;
        });
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function checker(): BelongsTo { return $this->belongsTo(Employee::class, 'checked_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function authorizer(): BelongsTo { return $this->belongsTo(Employee::class, 'authorized_by'); }
    public function clearance(): HasOne { return $this->hasOne(TravelAdvanceClearance::class); }
}
