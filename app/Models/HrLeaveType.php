<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrLeaveType extends Model
{
    protected $table = 'hr_leave_types';

    protected $fillable = [
        'name',
        'is_annual',
        'is_paid',
        'is_working_days',
        'is_hourly',
        'is_fixed',
        'max_days',
        'default_days',
        'requires_document',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_annual'         => 'boolean',
        'is_paid'           => 'boolean',
        'is_working_days'   => 'boolean',
        'is_hourly'         => 'boolean',
        'is_fixed'          => 'boolean',
        'requires_document' => 'boolean',
        'is_active'         => 'boolean',
        'max_days'          => 'integer',
        'default_days'      => 'integer',
    ];

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(HrLeaveRequest::class);
    }

    /** Fetch the single annual leave type. */
    public static function annual(): ?self
    {
        return static::where('is_annual', true)->where('is_active', true)->first();
    }
}
