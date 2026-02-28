<?php

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeDisaggregationCategory extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_disaggregation_categories';

    protected $fillable = [
        'key',
        'name',
        'rules',
    ];

    protected $casts = [
        'rules' => 'array',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(MeDisaggregationOption::class, 'category_id');
    }

    public function indicators(): BelongsToMany
    {
        return $this->belongsToMany(
            MeIndicator::class,
            'me_indicator_disaggregation',
            'category_id',
            'indicator_id'
        );
    }

    public function reportValues(): HasMany
    {
        return $this->hasMany(MeReportDisaggregationValue::class, 'category_id');
    }
}
