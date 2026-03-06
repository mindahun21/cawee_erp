<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delegation extends Model
{
    protected $table = 'hr_delegations';

    protected $fillable = [
        'delegator_id',
        'delegate_id',
        'start_date',
        'end_date',
        'subject',
        'scope',
        'reason',
        'status',
        'approved_by',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function delegator(): BelongsTo { return $this->belongsTo(Employee::class, 'delegator_id'); }
    public function delegate(): BelongsTo  { return $this->belongsTo(Employee::class, 'delegate_id'); }
    public function approver(): BelongsTo  { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
}
