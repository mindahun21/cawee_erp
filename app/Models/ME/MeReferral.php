<?php

declare(strict_types=1);

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeReferral extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_referrals';

    protected $fillable = [
        'beneficiary_id',
        'project_id',
        'referred_by',
        'referral_type',
        'referred_to',
        'reason',
        'referral_date',
        'status',
        'outcome',
        'completed_at',
    ];

    protected $casts = [
        'referral_date'  => 'date',
        'completed_at'   => 'date',
        'beneficiary_id' => 'integer',
        'project_id'     => 'integer',
        'referred_by'    => 'integer',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(MeBeneficiary::class, 'beneficiary_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(MeProject::class, 'project_id');
    }

    public function referredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed'   => 'success',
            'in_progress' => 'info',
            'pending'     => 'warning',
            'cancelled'   => 'danger',
            default       => 'gray',
        };
    }
}
