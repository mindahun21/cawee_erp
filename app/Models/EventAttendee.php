<?php

namespace App\Models;

use App\Observers\EventAttendeeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(EventAttendeeObserver::class)]
class EventAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_event_id',
        'donor_id',
        'name',
        'email',
        'phone',
        'status',
        'guests',
        'tickets_purchased',
        'amount_paid',
        'notes',
        'check_in_time',
        'check_out_time',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
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
