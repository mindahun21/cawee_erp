<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PlanResourceAllocation extends Model
{
    protected $fillable = [
        'plan_id',
        'resourceable_type',
        'resourceable_id',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the parent resourceable model (Employee, Item, etc.).
     */
    public function resourceable(): MorphTo
    {
        return $this->morphTo();
    }
}
