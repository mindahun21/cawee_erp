<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'campaign_id',
        'event_name',
        'event_type',
        'status',
        'event_date',
        'end_date',
        'venue',
        'venue_address',
        'description',
        'expected_attendees',
        'max_capacity',
        'rsvp_required',
        'rsvp_deadline',
        'ticket_price',
        'tickets_sold',
        'budget',
        'actual_cost',
        'funds_raised',
        'funds_to_campaign',
        'organizer_name',
        'organizer_email',
        'organizer_phone',
        'registration_link',
        'social_media_link',
        'volunteers_needed',
        'volunteers_registered',
        'notes',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'end_date' => 'datetime',
        'rsvp_deadline' => 'datetime',
        'ticket_price' => 'decimal:2',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'funds_raised' => 'decimal:2',
        'rsvp_required' => 'boolean',
        'funds_to_campaign' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function volunteers(): HasMany
    {
        return $this->hasMany(EventVolunteer::class);
    }
}
