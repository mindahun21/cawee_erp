<?php

namespace App\Filament\Pages\ME;

use App\Filament\Widgets\ME\MeIndicatorPerformanceTableWidget;
use App\Filament\Widgets\ME\MeKpiStatsWidget;
use App\Filament\Widgets\ME\MeLocationMapPlaceholderWidget;
use App\Filament\Widgets\ME\MePerformanceTrendChartWidget;
use App\Filament\Widgets\ME\MeProgressByFrameworkChartWidget;
use App\Models\ME\MeIndicatorReport;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

class MeDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static bool $isDiscovered = true;

    protected static string $routePath = 'me-dashboard';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string | \UnitEnum | null $navigationGroup = 'M&E';

    protected static ?string $navigationLabel = 'M&E Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Monitoring & Evaluation Dashboard';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                DatePicker::make('date_from')
                    ->label('Date From'),
                DatePicker::make('date_to')
                    ->label('Date To')
                    ->afterOrEqual('date_from'),
                Select::make('framework_type')
                    ->label('Framework')
                    ->options([
                        'output' => 'Output',
                        'outcome' => 'Outcome',
                        'impact' => 'Impact',
                    ])
                    ->placeholder('All'),
                Select::make('location')
                    ->label('Location')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => MeIndicatorReport::query()
                        ->whereNotNull('scope_location')
                        ->when(
                            $search !== '',
                            fn ($query) => $query->where('scope_location', 'like', '%' . $search . '%')
                        )
                        ->groupBy('scope_location')
                        ->orderBy('scope_location')
                        ->limit(50)
                        ->pluck('scope_location', 'scope_location')
                        ->toArray())
                    ->getOptionLabelUsing(fn ($value): ?string => is_string($value) && ($value !== '') ? $value : null)
                    ->placeholder('All'),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            MeKpiStatsWidget::class,
            MeProgressByFrameworkChartWidget::class,
            MePerformanceTrendChartWidget::class,
            MeLocationMapPlaceholderWidget::class,
            MeIndicatorPerformanceTableWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
