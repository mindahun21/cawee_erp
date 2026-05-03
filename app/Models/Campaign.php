<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'goal_amount',
        'currency_id',
        'start_date',
        'end_date',
        'budget',
        'status',
        'total_raised',
        'donor_count',
        'exchange_rate',
        'base_goal_amount',
        'base_budget',
    ];

    protected $casts = [
        'goal_amount' => 'decimal:2',
        'budget' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'base_goal_amount' => 'decimal:2',
        'base_budget' => 'decimal:2',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CampaignEvent::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($campaign) {
            if ($campaign->exchange_rate === null) {
                $campaign->exchange_rate = 1.000000;
            }
            if ($campaign->base_goal_amount === null && $campaign->goal_amount !== null) {
                $campaign->base_goal_amount = $campaign->goal_amount * $campaign->exchange_rate;
            }
            if ($campaign->base_budget === null && $campaign->budget !== null) {
                $campaign->base_budget = $campaign->budget * $campaign->exchange_rate;
            }
        });
        
        static::updating(function ($campaign) {
            if ($campaign->isDirty(['goal_amount', 'exchange_rate'])) {
                $campaign->base_goal_amount = $campaign->goal_amount * ($campaign->exchange_rate ?? 1);
            }
            if ($campaign->isDirty(['budget', 'exchange_rate'])) {
                $campaign->base_budget = $campaign->budget * ($campaign->exchange_rate ?? 1);
            }
        });
    }
}
