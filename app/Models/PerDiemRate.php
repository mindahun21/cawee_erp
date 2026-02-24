<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerDiemRate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'label', 'position_pattern', 'project_id', 'location_id',
        'rate_per_day', 'currency', 'is_active', 'effective_from', 'effective_to', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to'   => 'date',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
}
