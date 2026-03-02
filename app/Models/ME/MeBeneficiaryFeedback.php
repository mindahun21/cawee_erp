<?php

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeBeneficiaryFeedback extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_beneficiary_feedback';

    public $timestamps = false;

    protected $fillable = [
        'submitted_at',
        'location',
        'sentiment',
        'comment',
        'metadata',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'metadata' => 'array',
    ];
}
