<?php

namespace App\Filament\Resources\HR\Timesheets;

use App\Filament\Resources\HR\Timesheets\TimesheetResource\Pages;
use App\Models\HrTimesheet;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class TimesheetResource extends Resource
{
    protected static ?string $model = HrTimesheet::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Human Resources';
 
    protected static ?int $navigationSort = 20;
 
    protected static ?string $navigationLabel = 'Timesheets';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('General Information')
                ->columns(2)
                ->columnSpanFull()
                ->components([

                    Select::make('employee_id')
                        ->relationship('employee', 'first_name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} (" . ($record->department?->name ?? 'No Dept') . " - " . ($record->jobPosition?->name ?? 'No Position') . ")")
                        ->searchable(['first_name', 'last_name'])
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($livewire, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) {
                            static::checkExistingTimesheet($livewire, $set, $get);
                        }),

                    Select::make('location_id')
                        ->relationship('location', 'location_name')
                        ->searchable()
                        ->required(),

                    Select::make('month')
                        ->options([
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                        ])
                        ->required()
                        ->default(date('n'))
                        ->live()
                        ->afterStateUpdated(function ($livewire, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) {
                            static::checkExistingTimesheet($livewire, $set, $get);
                        }),

                    TextInput::make('year')
                        ->numeric()
                        ->default(date('Y'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($livewire, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) {
                            static::checkExistingTimesheet($livewire, $set, $get);
                        }),

                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'submitted' => 'Submitted',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])
                        ->default('draft')
                        ->required(),
                ]),

            Section::make('Timesheet Grid')
                ->description('Log employee daily work hours, project tasks, and descriptions.')
                ->columnSpanFull()
                ->components([

                    ViewField::make('timesheet_data')
                        ->view('filament.forms.components.timesheet-grid')
                        ->columnSpanFull()
                        ->hiddenLabel()
                        ->viewData([
                            'locations' => \App\Models\Location::get(['id', 'location_name']),
                            'projects' => \App\Models\Project::get(['id', 'project_name', 'project_code']),
                            'leaveTypes' => \App\Models\HrLeaveType::where('is_active', true)->get(['id', 'name']),
                        ])
                        ->default(fn (callable $get) => \App\Models\HrTimesheet::generatePreviewData(
                            $get('employee_id'),
                            $get('month') ?? date('n'),
                            $get('year') ?? date('Y')
                        )),

                    Placeholder::make('info')
                        ->content('Leave requests are handled in a separate HR Leave module. Timesheets only track work hours.')
                        ->hiddenLabel(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('employee.first_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($record) => "{$record->employee->first_name} {$record->employee->last_name}"),

                TextColumn::make('employee.department.name')
                    ->label('Department')
                    ->sortable(),

                TextColumn::make('month')
                    ->formatStateUsing(fn ($state) => date('F', mktime(0, 0, 0, $state, 10)))
                    ->sortable(),

                TextColumn::make('year')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('total_hours')
                    ->label('Total Hours')
                    ->getStateUsing(fn ($record) => (float) $record->entries()->sum('hours') + (float) $record->leaves()->sum('hours'))
                    ->suffix(' hrs')
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->color('primary'),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('department')
                    ->relationship('employee.department', 'name')
                    ->label('Filter by Department'),
                
                \Filament\Tables\Filters\SelectFilter::make('month')
                    ->options([
                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                    ]),
                
                \Filament\Tables\Filters\SelectFilter::make('year')
                    ->options(collect(range(date('Y') - 5, date('Y') + 1))->mapWithKeys(fn ($y) => [$y => $y])),

                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimesheets::route('/'),
            'create' => Pages\CreateTimesheet::route('/create'),
            'edit' => Pages\EditTimesheet::route('/{record}/edit'),
        ];
    }

    /**
     * Check if a timesheet already exists for the selected employee+month+year.
     * If on Create page and record exists, redirect to edit it.
     * Otherwise, load existing data into the grid.
     */
    public static function checkExistingTimesheet($livewire, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get): void
    {
        $employeeId = $get('employee_id');
        $month = $get('month');
        $year = $get('year');

        if (!$month || !$year) {
            $set('timesheet_data', [
                'projects' => [],
                'leaves' => [],
                'daily_details' => [],
                'holidays' => [],
            ]);
            return;
        }

        if (!$employeeId) {
            $set('timesheet_data', HrTimesheet::generatePreviewData(null, $month, $year));
            return;
        }

        $existing = HrTimesheet::where('employee_id', $employeeId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existing) {
            $currentId = $livewire->getRecord()?->id ?? null;
            if ($existing->id !== $currentId) {
                // REDIRECT TO EXISTING EDIT PAGE
                Notification::make()
                    ->title('Switching to existing record')
                    ->body("A timesheet for {$month}/{$year} already exists. Redirecting...")
                    ->info()
                    ->send();

                redirect(static::getUrl('edit', ['record' => $existing->id]));
                return;
            }
            $set('timesheet_data', $existing->timesheet_data);
        } else {
            // IF WE ARE ON EDIT PAGE AND NO COMBINATION EXISTS
            // WE MUST REDIRECT TO CREATE PAGE TO PREVENT OVERWRITING THE OLD MONTH
            $currentId = $livewire->getRecord()?->id ?? null;
            if ($currentId) {
                Notification::make()
                    ->title('Creating new record')
                    ->body("No timesheet found for this period. Moving to Create page...")
                    ->info()
                    ->send();

                redirect(static::getUrl('create', [
                    'employee_id' => $employeeId,
                    'month' => $month,
                    'year' => $year
                ]));
                return;
            }
            $set('timesheet_data', HrTimesheet::generatePreviewData($employeeId, $month, $year));
        }
    }
}