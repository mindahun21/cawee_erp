<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class RecruitmentOffer extends Model
{
    protected $table = 'recruitment_offers';

    protected $fillable = [
        'application_id',
        'offered_salary',
        'offer_letter_path',
        'offer_date',
        'offer_expiry_date',
        'status',
        'responded_at',
        'decline_reason',
        'issued_by',
    ];

    protected $casts = [
        'offer_date' => 'date',
        'offer_expiry_date' => 'date',
        'responded_at' => 'datetime',
        'offered_salary' => 'decimal:2',
    ];

    public function application(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class);
    }

    public function issuer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'issued_by');
    }
}
