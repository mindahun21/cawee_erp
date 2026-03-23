<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementRenewal extends Model
{
    protected $table = 'hr_agreement_renewals';

    protected $fillable = [
        'office_rent_agreement_id',
        'decision_option_id',
        'new_monthly_rent',
        'new_start_date',
        'new_end_date',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'new_monthly_rent' => 'decimal:2',
            'new_start_date' => 'date',
            'new_end_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(OfficeRentAgreement::class, 'office_rent_agreement_id');
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(VehicleSetting::class, 'decision_option_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

