<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventVolunteer extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_event_id',
        'donor_id',
        'name',
        'email',
        'phone',
        'role',
        'tasks',
        'hours_committed',
        'hours_completed',
        'check_in_time',
        'check_out_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'hours_committed' => 'decimal:2',
        'hours_completed' => 'decimal:2',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CampaignEvent::class, 'campaign_event_id');
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }
}
