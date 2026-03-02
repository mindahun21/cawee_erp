<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $table = 'payroll';

    protected $fillable = [
        'employee_id',
        'year',
        'month',
        'basic_salary',
        'transport_allowance',
        'house_allowance',
        'communications_allowance',
        'overtime_allowance',
        'incentive',
        'other_allowances',
        'total_compensation',
    ];

    protected $casts = [
        'basic_salary'             => 'decimal:2',
        'transport_allowance'      => 'decimal:2',
        'house_allowance'          => 'decimal:2',
        'communications_allowance' => 'decimal:2',
        'overtime_allowance'       => 'decimal:2',
        'incentive'                => 'decimal:2',
        'other_allowances'         => 'decimal:2',
        'total_compensation'       => 'decimal:2',
    ];

    // Recompute total before saving
    protected static function booted(): void
    {
        static::saving(function (self $payroll) {
            $payroll->total_compensation =
                (float) $payroll->basic_salary
                + (float) $payroll->transport_allowance
                + (float) $payroll->house_allowance
                + (float) $payroll->communications_allowance
                + (float) $payroll->overtime_allowance
                + (float) $payroll->incentive
                + (float) $payroll->other_allowances;
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
