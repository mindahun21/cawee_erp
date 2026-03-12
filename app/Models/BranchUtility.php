<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchUtility extends Model
{
    use SoftDeletes;

    protected $table = 'hr_branch_utilities';

    protected $fillable = [
        'branch_id',
        'utility_type_option_id',
        'provider',
        'account_number',
        'payment_cycle_option_id',
        'estimated_amount',
        'next_due_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_amount' => 'decimal:2',
            'next_due_date' => 'date',
        ];
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (! $this->next_due_date) {
            return null;
        }

        return (int) now()->diffInDays($this->next_due_date, false);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(HrBranch::class, 'branch_id');
    }

    public function utilityType(): BelongsTo
    {
        return $this->belongsTo(HrSettingOption::class, 'utility_type_option_id');
    }

    public function paymentCycle(): BelongsTo
    {
        return $this->belongsTo(HrSettingOption::class, 'payment_cycle_option_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(UtilityPayment::class, 'branch_utility_id');
    }
}

