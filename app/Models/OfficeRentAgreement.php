<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfficeRentAgreement extends Model
{
    use SoftDeletes;

    protected $table = 'hr_office_rent_agreements';

    protected $fillable = [
        'agreement_code',
        'branch_id',
        'landlord_id',
        'payment_cycle_option_id',
        'property_address',
        'monthly_rent',
        'start_date',
        'end_date',
        'contract_document_path',
        'status',
        'legal_reviewed_by',
        'legal_reviewed_at',
        'activated_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'monthly_rent' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'legal_reviewed_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $agreement): void {
            if (! empty($agreement->agreement_code)) {
                return;
            }

            $year = now()->format('Y');
            $seq = static::withTrashed()->whereYear('created_at', now()->year)->count() + 1;
            $agreement->agreement_code = sprintf('ORA-%s-%04d', $year, $seq);
        });
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        return (int) now()->diffInDays($this->end_date, false);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(HrBranch::class, 'branch_id');
    }

    public function landlord(): BelongsTo
    {
        return $this->belongsTo(Landlord::class, 'landlord_id');
    }

    public function paymentCycle(): BelongsTo
    {
        return $this->belongsTo(VehicleSetting::class, 'payment_cycle_option_id');
    }

    public function legalReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'legal_reviewed_by');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(AgreementRenewal::class, 'office_rent_agreement_id');
    }
}

