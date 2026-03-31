<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonorInteraction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'donor_id',
        'interaction_type',
        'interaction_date',
        'subject',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'interaction_date' => 'datetime',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
