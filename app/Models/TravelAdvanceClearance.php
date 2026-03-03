<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelAdvanceClearance extends Model
{
    protected $fillable = [
        'travel_advance_id', 'actual_depart_date', 'actual_return_date',
        'actual_days_spent', 'per_diem_settled', 'accommodation_settled',
        'transport_settled', 'other_settled', 'total_settled',
        'advance_received', 'net_due', 'pv_number', 'rv_number',
        'checked_by', 'checked_at', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'actual_depart_date' => 'date',
            'actual_return_date' => 'date',
            'checked_at'         => 'datetime',
            'approved_at'        => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $c) {
            $c->total_settled = (float) $c->per_diem_settled
                + (float) $c->accommodation_settled
                + (float) $c->transport_settled
                + (float) $c->other_settled;
            $c->net_due = (float) $c->total_settled - (float) $c->advance_received;
        });
    }

    public function travelAdvance(): BelongsTo { return $this->belongsTo(TravelAdvance::class); }
    public function checker(): BelongsTo { return $this->belongsTo(Employee::class, 'checked_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(Employee::class, 'approved_by'); }
}
