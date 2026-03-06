<?php

namespace App\Models\ME;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeReportDisaggregationValue extends Model
{
    use HasFactory;

    protected $table = 'me_report_disaggregation_values';

    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'category_id',
        'option_id',
        'value',
    ];

    protected $casts = [
        'report_id' => 'integer',
        'category_id' => 'integer',
        'option_id' => 'integer',
        'value' => 'decimal:2',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(MeIndicatorReport::class, 'report_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MeDisaggregationCategory::class, 'category_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(MeDisaggregationOption::class, 'option_id');
    }
}
