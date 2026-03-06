<?php

namespace App\Models\ME;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeIndicatorDisaggregation extends Model
{
    use HasFactory;

    protected $table = 'me_indicator_disaggregation';

    public $timestamps = false;

    protected $fillable = [
        'indicator_id',
        'category_id',
    ];

    protected $casts = [
        'indicator_id' => 'integer',
        'category_id' => 'integer',
    ];

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(MeIndicator::class, 'indicator_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MeDisaggregationCategory::class, 'category_id');
    }
}
