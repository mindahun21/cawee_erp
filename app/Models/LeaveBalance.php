<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = [
        'employee_id', 'year',
        'annual_entitled', 'annual_used', 'annual_balance',
        'sick_entitled', 'sick_used', 'sick_balance',
        'maternity_entitled', 'maternity_used', 'maternity_balance',
        'field_entitled', 'field_used', 'field_balance',
        'remarks',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // Recalculate balances from entitlement - used
    protected static function booted(): void
    {
        static::saving(function (self $balance) {
            $balance->annual_balance    = (float) $balance->annual_entitled    - (float) $balance->annual_used;
            $balance->sick_balance      = (float) $balance->sick_entitled      - (float) $balance->sick_used;
            $balance->maternity_balance = (float) $balance->maternity_entitled - (float) $balance->maternity_used;
            $balance->field_balance     = (float) $balance->field_entitled     - (float) $balance->field_used;
        });
    }
}
