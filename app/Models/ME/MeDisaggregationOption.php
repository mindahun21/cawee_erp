<?php

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class MeDisaggregationOption extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_disaggregation_options';

    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'value',
        'label',
        'sort_order',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (MeDisaggregationOption $option): void {
            $category = $option->relationLoaded('category')
                ? $option->category
                : $option->category()->first();

            if (! $category || $category->key !== 'age') {
                return;
            }

            $buckets = collect(data_get($category->rules, 'buckets', []))
                ->map(function ($bucket): string {
                    if (is_array($bucket)) {
                        return (string) ($bucket['value'] ?? '');
                    }

                    return (string) $bucket;
                })
                ->map(fn (string $bucket): string => trim($bucket))
                ->filter()
                ->values();

            if ($buckets->isEmpty()) {
                return;
            }

            if (! $buckets->contains((string) $option->value)) {
                throw ValidationException::withMessages([
                    'value' => 'Age option value must match a configured age bucket in category rules.',
                ]);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MeDisaggregationCategory::class, 'category_id');
    }

    public function reportValues(): HasMany
    {
        return $this->hasMany(MeReportDisaggregationValue::class, 'option_id');
    }
}
