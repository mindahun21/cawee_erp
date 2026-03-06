<?php

namespace App\Imports\ME;

use App\Models\ME\MeDisaggregationCategory;
use App\Models\ME\MeDisaggregationOption;
use App\Models\ME\MeIndicator;
use App\Models\ME\MeIndicatorReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WeeklyReportImport implements ToCollection, WithHeadingRow
{
    public int $importedCount = 0;

    public function collection(Collection $rows): void
    {
        $rows->each(function ($row): void {
            if ($row instanceof Collection) {
                $row = $row->toArray();
            } elseif (! is_array($row)) {
                $row = (array) $row;
            }

            $indicatorCode = trim((string) ($row['indicator_code'] ?? ''));

            if ($indicatorCode === '') {
                return;
            }

            $indicator = $this->resolveIndicator($indicatorCode);

            if (! $indicator) {
                throw ValidationException::withMessages([
                    'indicator_code' => "Indicator '{$indicatorCode}' was not found (checked by code and numeric ID).",
                ]);
            }

            $periodStart = Carbon::parse($row['period_start'] ?? null)->toDateString();
            $periodEnd = Carbon::parse($row['period_end'] ?? null)->toDateString();
            $actualValue = (float) ($row['actual_value'] ?? 0);

            $report = MeIndicatorReport::query()->create([
                'indicator_id' => $indicator->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'actual_value' => $actualValue,
                'scope_location' => $this->nullableString($row['scope_location'] ?? null),
                'scope_project' => $this->nullableString($row['scope_project'] ?? null),
            ]);

            $disaggregationInput = collect([
                'gender' => $this->nullableString($row['gender'] ?? null),
                'age' => $this->nullableString($row['age'] ?? null),
                'disability' => $this->nullableString($row['disability'] ?? null),
            ])->filter();

            if ($indicator->disaggregation_required && $disaggregationInput->isEmpty()) {
                throw ValidationException::withMessages([
                    'disaggregation' => "Indicator '{$indicatorCode}' requires disaggregation values.",
                ]);
            }

            if ($disaggregationInput->isNotEmpty()) {
                $this->createDisaggregationValues($report, $disaggregationInput->all(), $actualValue);
            }

            $sum = (float) $report->disaggregationValues()->sum('value');
            if ($indicator->disaggregation_required && abs($sum - $actualValue) > 0.01) {
                throw ValidationException::withMessages([
                    'disaggregation' => 'Disaggregation sum must equal actual value (tolerance 0.01).',
                ]);
            }

            $this->importedCount++;
        });
    }

    private function createDisaggregationValues(MeIndicatorReport $report, array $inputs, float $actualValue): void
    {
        $total = count($inputs);
        $distributed = [];

        if ($total > 0) {
            $base = round($actualValue / $total, 2);
            $allocated = 0.0;

            foreach (array_keys($inputs) as $index => $key) {
                if ($index === $total - 1) {
                    $distributed[$key] = round($actualValue - $allocated, 2);
                } else {
                    $distributed[$key] = $base;
                    $allocated += $base;
                }
            }
        }

        foreach ($inputs as $key => $optionValue) {
            $category = MeDisaggregationCategory::query()->where('key', $key)->first();

            if (! $category) {
                continue;
            }

            $option = MeDisaggregationOption::query()->firstOrCreate(
                [
                    'category_id' => $category->id,
                    'value' => $optionValue,
                ],
                [
                    'label' => ucwords(str_replace('_', ' ', $optionValue)),
                    'sort_order' => 0,
                ]
            );

            $report->disaggregationValues()->create([
                'category_id' => $category->id,
                'option_id' => $option->id,
                'value' => $distributed[$key] ?? 0,
            ]);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolveIndicator(string $reference): ?MeIndicator
    {
        $normalized = mb_strtolower($reference);

        $indicator = MeIndicator::query()
            ->where('code', $reference)
            ->first();

        if ($indicator) {
            return $indicator;
        }

        $indicator = MeIndicator::query()
            ->whereRaw('LOWER(TRIM(code)) = ?', [$normalized])
            ->first();

        if ($indicator) {
            return $indicator;
        }

        if (ctype_digit($reference)) {
            return MeIndicator::query()->find((int) $reference);
        }

        return null;
    }
}
