<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton: one row stores the organisation-wide leave accrual policy.
 * All values can be edited in HR Settings without a code deploy.
 */
class HrLeavePolicy extends Model
{
    protected $table = 'hr_leave_policies';

    protected $fillable = [
        'era_boundary_date',
        'pre_era_base_days',
        'pre_era_accrual_per_year',
        'post_era_base_days',
        'post_era_accrual_every_n_years',
        'fifo_window_years',
        'skip_sundays',
        'skip_public_holidays',
        'fiscal_year_month_day',
    ];

    protected $casts = [
        'era_boundary_date'              => 'date',
        'pre_era_base_days'              => 'integer',
        'pre_era_accrual_per_year'       => 'integer',
        'post_era_base_days'             => 'integer',
        'post_era_accrual_every_n_years' => 'integer',
        'fifo_window_years'              => 'integer',
        'skip_sundays'                   => 'boolean',
        'skip_public_holidays'           => 'boolean',
    ];

    /** Always fetch the single policy row (never null after migration seeding). */
    public static function current(): self
    {
        return static::first() ?? new static([
            'era_boundary_date'              => '2019-07-08',
            'pre_era_base_days'              => 14,
            'pre_era_accrual_per_year'       => 1,
            'post_era_base_days'             => 16,
            'post_era_accrual_every_n_years' => 2,
            'fifo_window_years'              => 3,
            'skip_sundays'                   => true,
            'skip_public_holidays'           => true,
            'fiscal_year_month_day'          => '07-08',
        ]);
    }
}
