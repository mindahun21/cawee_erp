<?php

namespace App\Filament\Clusters\Planning\Pages;

use App\Filament\Clusters\Planning;
use App\Models\Plan;
use App\Models\Task;
use App\Models\PlanningKpi;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use BackedEnum;
use UnitEnum;

class PlanningReports extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $cluster = Planning::class;

    protected string $view = 'filament.clusters.planning.pages.planning-reports';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $title = 'Planning & Performance Reports';

    protected static ?int $navigationSort = 10;

    public ?string $activeTab = 'kpis';

    public ?array $filters = [
        'period' => 'this_year',
        'department_id' => null,
    ];

    public function mount(): void
    {
        $this->activeTab = request()->query('tab', 'kpis');
        $this->form->fill($this->filters);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('period')
                                    ->label('Time Period')
                                    ->options([
                                        'this_month' => 'This Month',
                                        'this_quarter' => 'This Quarter',
                                        'this_year' => 'This Year',
                                        'all_time' => 'All Time',
                                    ])
                                    ->default('this_year')
                                    ->live(),
                                Select::make('department_id')
                                    ->label('Department')
                                    ->options(\App\Models\Department::pluck('name', 'id'))
                                    ->placeholder('All Departments')
                                    ->preload()
                                    ->searchable()
                                    ->live(),
                            ]),
                    ])
                    ->compact(),
            ])
            ->statePath('filters');
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getReportColumns())
            ->emptyStateHeading('No data found for this report')
            ->paginated([10, 25, 50]);
    }

    protected function getTableQuery(): Builder
    {
        $deptId = $this->filters['department_id'];
        
        return match ($this->activeTab) {
            'plans' => Plan::query()
                ->when($deptId, fn($q) => $q->where('department_id', $deptId)),
            
            'tasks' => Task::query()
                ->with(['plan', 'employee'])
                ->when($deptId, fn($q) => $q->whereHas('plan', fn($pq) => $pq->where('department_id', $deptId))),

            default => PlanningKpi::query()
                ->with(['plan'])
                ->when($deptId, fn($q) => $q->whereHas('plan', fn($pq) => $pq->where('department_id', $deptId))),
        };
    }

    protected function getReportColumns(): array
    {
        return match ($this->activeTab) {
            'plans' => [
                TextColumn::make('title')->searchable()->weight('bold'),
                TextColumn::make('type')->badge()->color('gray'),
                TextColumn::make('status')->badge()->color(fn($state) => match($state){
                    'active' => 'success',
                    'draft' => 'gray',
                    default => 'primary'
                }),
                TextColumn::make('progress_percentage')->label('Progress')->numeric()->suffix('%'),
            ],
            'tasks' => [
                TextColumn::make('title')->searchable(),
                TextColumn::make('plan.title')->label('Parent Plan')->limit(30),
                TextColumn::make('employee.full_name')->label('Assigned To'),
                TextColumn::make('status')->badge(),
                TextColumn::make('deadline')->date(),
            ],
            default => [
                TextColumn::make('indicator_name')->label('KPI Indicator')->searchable()->weight('bold'),
                TextColumn::make('plan.title')->label('Plan')->limit(30),
                TextColumn::make('target_value')->numeric()->alignEnd(),
                TextColumn::make('actual_value')->numeric()->alignEnd(),
                TextColumn::make('variance')
                    ->label('Variance')
                    ->formatStateUsing(fn($record) => $record->target_value - $record->actual_value)
                    ->color(fn($record) => ($record->target_value - $record->actual_value) > 0 ? 'danger' : 'success')
                    ->alignEnd(),
            ],
        };
    }

    protected function getViewData(): array
    {
        return [
            'stats' => [
                'total_plans' => Plan::count(),
                'avg_progress' => round(Plan::avg('progress_percentage') ?? 0, 1),
                'overdue_tasks' => Task::where('status', '!=', 'completed')->where('deadline', '<', now())->count(),
            ],
        ];
    }
}
