<?php

namespace App\Filament\Widgets\ME;

use App\Filament\Widgets\ME\Concerns\InteractsWithMeFilters;
use App\Models\ME\MeIndicator;
use App\Services\ME\DashboardService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class MeIndicatorPerformanceTableWidget extends BaseWidget
{
    use InteractsWithMeFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Indicator Performance';

    protected int | string | array $columnSpan = 'full';

    private ?Collection $performanceRows = null;
    private ?string $performanceRowsCacheKey = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MeIndicator::query()->whereIn('id', $this->getPerformanceRows()->keys()->all())
            )
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('name')
                    ->label('Indicator')
                    ->searchable()
                    ->limit(48)
                    ->tooltip(fn (MeIndicator $record): string => (string) $record->name)
                    ->wrap(),
                TextColumn::make('framework_type')
                    ->label('Framework')
                    ->badge(),
                TextColumn::make('latest_target')
                    ->label('Target')
                    ->state(fn (MeIndicator $record): string => number_format((float) ($this->rowFor($record->id)['latest_target'] ?? 0), 2)),
                TextColumn::make('latest_actual')
                    ->label('Actual')
                    ->state(fn (MeIndicator $record): string => number_format((float) ($this->rowFor($record->id)['latest_actual'] ?? 0), 2)),
                TextColumn::make('progress_percent')
                    ->label('Progress %')
                    ->state(fn (MeIndicator $record): string => number_format((float) ($this->rowFor($record->id)['progress_percent'] ?? 0), 2) . '%')
                    ->badge()
                    ->color(function (MeIndicator $record): string {
                        $status = $this->rowFor($record->id)['status'] ?? 'off_track';

                        return match ($status) {
                            'on_track' => 'success',
                            'needs_attention' => 'warning',
                            default => 'danger',
                        };
                    }),
                TextColumn::make('status')
                    ->state(fn (MeIndicator $record): string => str_replace('_', ' ', (string) ($this->rowFor($record->id)['status'] ?? 'off_track')))
                    ->badge()
                    ->color(function (MeIndicator $record): string {
                        $status = $this->rowFor($record->id)['status'] ?? 'off_track';

                        return match ($status) {
                            'on_track' => 'success',
                            'needs_attention' => 'warning',
                            default => 'danger',
                        };
                    }),
                TextColumn::make('last_reported_date')
                    ->label('Last Reported')
                    ->state(fn (MeIndicator $record): string => (string) ($this->rowFor($record->id)['last_reported_date'] ?? '-')),
            ])
            ->defaultSort('code')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50]);
    }

    private function getPerformanceRows(): Collection
    {
        $cacheKey = md5(json_encode($this->getMeFilters()));

        if (($this->performanceRows !== null) && ($this->performanceRowsCacheKey === $cacheKey)) {
            return $this->performanceRows;
        }

        $rows = app(DashboardService::class)
            ->indicatorPerformance($this->getMeFilters())
            ->keyBy('indicator_id');

        $this->performanceRows = $rows;
        $this->performanceRowsCacheKey = $cacheKey;

        return $this->performanceRows;
    }

    private function rowFor(int $indicatorId): array
    {
        return $this->getPerformanceRows()->get($indicatorId, []);
    }
}
