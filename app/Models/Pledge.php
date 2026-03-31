<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pledge extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'donor_id',
        'campaign_id',
        'total_amount',
        'currency_id',
        'start_date',
        'end_date',
        'frequency',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function getFulfilledAmountAttribute(): float
    {
        return (float) $this->donations()->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) ($this->total_amount - $this->fulfilled_amount);
    }

    public function getPercentFulfilledAttribute(): float
    {
        if ($this->total_amount <= 0) return 0;
        return round(($this->fulfilled_amount / $this->total_amount) * 100, 2);
    }
}
