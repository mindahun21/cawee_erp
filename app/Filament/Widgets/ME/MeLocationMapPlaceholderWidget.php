<?php

namespace App\Filament\Widgets\ME;

use App\Filament\Widgets\ME\Concerns\InteractsWithMeFilters;
use App\Models\ME\MeIndicatorReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MeLocationMapPlaceholderWidget extends BaseWidget
{
    use InteractsWithMeFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Location Progress Summary';

    protected int | string | array $columnSpan = 1;

    private ?Collection $locationStats = null;
    private ?string $locationStatsCacheKey = null;

    public function table(Table $table): Table
    {
        $locations = $this->getLocationStats()->keys()->values()->all();

        $query = MeIndicatorReport::query()
            ->when($locations === [], fn (Builder $builder) => $builder->whereRaw('1 = 0'))
            ->when($locations !== [], fn (Builder $builder) => $builder->whereIn('id', function ($sub) use ($locations) {
                $sub->selectRaw('MIN(id)')
                    ->from('me_indicator_reports')
                    ->whereNotNull('scope_location')
                    ->whereIn('scope_location', $locations)
                    ->groupBy('scope_location');
            }));

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('scope_location')
                    ->label('Location'),
                TextColumn::make('average_progress')
                    ->label('Avg Progress %')
                    ->state(fn (MeIndicatorReport $record): string => number_format((float) ($this->getLocationStats()->get($record->scope_location)['average_progress'] ?? 0), 2) . '%')
                    ->badge()
                    ->color(function (MeIndicatorReport $record): string {
                        $value = (float) ($this->getLocationStats()->get($record->scope_location)['average_progress'] ?? 0);

                        if ($value >= 90) {
                            return 'success';
                        }

                        if ($value >= 70) {
                            return 'warning';
                        }

                        return 'danger';
                    }),
                TextColumn::make('reports_count')
                    ->label('Report Count')
                    ->state(fn (MeIndicatorReport $record): string => (string) ($this->getLocationStats()->get($record->scope_location)['reports_count'] ?? 0)),
            ])
            ->striped()
            ->emptyStateHeading('No location-level reports for the selected filters.')
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50]);
    }

    private function getLocationStats(): Collection
    {
        $cacheKey = md5(json_encode($this->getMeFilters()));

        if (($this->locationStats !== null) && ($this->locationStatsCacheKey === $cacheKey)) {
            return $this->locationStats;
        }

        $query = MeIndicatorReport::query()
            ->with('indicator')
            ->whereNotNull('scope_location');

        $filters = $this->getMeFilters();

        if (! empty($filters['date_from'])) {
            $query->whereDate('period_end', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('period_start', '<=', $filters['date_to']);
        }

        if (! empty($filters['location'])) {
            $query->where('scope_location', $filters['location']);
        }

        if (! empty($filters['framework_type'])) {
            $query->whereHas('indicator', fn (Builder $builder) => $builder->where('framework_type', $filters['framework_type']));
        }

        $this->locationStats = $query
            ->get()
            ->groupBy('scope_location')
            ->map(function (Collection $reports): array {
                return [
                    'average_progress' => round((float) $reports->avg(fn (MeIndicatorReport $report): float => $report->progressPercent()), 2),
                    'reports_count' => $reports->count(),
                ];
            });
        $this->locationStatsCacheKey = $cacheKey;

        return $this->locationStats;
    }
}
