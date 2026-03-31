<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'donor_type',
        'first_name',
        'last_name',
        'organization_name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'status',
        'notes',
        'total_donated',
        'last_donation_date',
    ];

    protected $casts = [
        'last_donation_date' => 'datetime',
        'total_donated' => 'decimal:2',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(DonorCategory::class, 'donor_category_donor');
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function attendedEvents(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function volunteeredEvents(): HasMany
    {
        return $this->hasMany(EventVolunteer::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(DonorInteraction::class);
    }

    public function pledges(): HasMany
    {
        return $this->hasMany(Pledge::class);
    }

    public function getFullNameAttribute(): string
    {
        if ($this->donor_type === 'individual') {
            return "{$this->first_name} {$this->last_name}";
        }

        return $this->organization_name ?? $this->email;
    }
}
