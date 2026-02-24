<?php

namespace App\Filament\Resources\HR\Employees;

use App\Filament\Resources\HR\Employees\Pages\CreateEmployee;
use App\Filament\Resources\HR\Employees\Pages\EditEmployee;
use App\Filament\Resources\HR\Employees\Pages\ListEmployees;
use App\Filament\Resources\HR\Employees\Pages\ViewEmployee;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'Employees';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->tabs([

                Tab::make('Personal Information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Section::make()->columns(2)->schema([
                            TextInput::make('first_name')
                                ->required()
                                ->maxLength(100),

                            TextInput::make('last_name')
                                ->required()
                                ->maxLength(100),

                            Select::make('gender')
                                ->options(['M' => 'Male', 'F' => 'Female'])
                                ->required(),

                            DatePicker::make('date_of_birth')
                                ->maxDate(now()->subYears(18)),

                            TextInput::make('national_id')
                                ->label('National ID')
                                ->maxLength(50),

                            TextInput::make('tin')
                                ->label('TIN')
                                ->maxLength(50),

                            TextInput::make('pension_id')
                                ->label('Pension ID')
                                ->maxLength(50),

                            TextInput::make('phone_number')
                                ->tel()
                                ->maxLength(20),

                            TextInput::make('email')
                                ->email()
                                ->maxLength(150),

                            TextInput::make('education_level')
                                ->maxLength(100),

                            TextInput::make('field_of_study')
                                ->maxLength(100),
                        ]),
                    ]),

                Tab::make('Employment Details')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Section::make()->columns(2)->schema([
                            TextInput::make('position')
                                ->required()
                                ->maxLength(150),

                            Select::make('employment_type')
                                ->options([
                                    'Contract'    => 'Contract',
                                    'Temporary'   => 'Temporary',
                                    'Consultancy' => 'Consultancy',
                                    'Other'       => 'Other',
                                ])
                                ->required(),

                            DatePicker::make('date_of_employment')
                                ->required(),

                            DatePicker::make('date_transferred'),

                            DatePicker::make('date_resigned'),

                            Select::make('location_id')
                                ->label('Location')
                                ->relationship('location', 'location_name')
                                ->searchable()
                                ->preload(),

                            Select::make('project_id')
                                ->label('Project')
                                ->relationship('project', 'project_name')
                                ->searchable()
                                ->preload(),

                            Select::make('salary_grade_id')
                                ->label('Salary Grade')
                                ->relationship('salaryGrade', 'grade')
                                ->getOptionLabelFromRecordUsing(fn ($record) => "Grade {$record->grade} - Step {$record->step} (ETB {$record->basic_salary})")
                                ->searchable()
                                ->preload()
                                ->helperText('Optional — link to a grade/step salary scale'),
                        ]),
                    ]),

                Tab::make('Compensation')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Section::make('Salary & Allowances')->columns(2)->schema([
                            TextInput::make('basic_salary')
                                ->numeric()
                                ->prefix('ETB')
                                ->minValue(0)
                                ->required(),

                            TextInput::make('transport_allowance')
                                ->numeric()
                                ->prefix('ETB')
                                ->minValue(0),

                            TextInput::make('house_allowance')
                                ->numeric()
                                ->prefix('ETB')
                                ->minValue(0),

                            TextInput::make('communication_allowance')
                                ->numeric()
                                ->prefix('ETB')
                                ->minValue(0),

                            TextInput::make('overtime_allowance')
                                ->numeric()
                                ->prefix('ETB')
                                ->minValue(0),

                            TextInput::make('incentive')
                                ->numeric()
                                ->prefix('ETB')
                                ->minValue(0),

                            TextInput::make('other_allowances')
                                ->numeric()
                                ->prefix('ETB')
                                ->minValue(0),
                        ]),
                    ]),

                Tab::make('Bank Accounts')
                    ->icon('heroicon-o-building-library')
                    ->schema([
                        Section::make()->columns(2)->schema([
                            TextInput::make('bank_account_awash')
                                ->label('Awash Bank Account')
                                ->maxLength(50),

                            TextInput::make('bank_account_orocoop')
                                ->label('Oromia Coop Bank Account')
                                ->maxLength(50),

                            TextInput::make('bank_account_other')
                                ->label('Other Bank Account')
                                ->maxLength(50),
                        ]),
                    ]),

                Tab::make('Remarks')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Textarea::make('remarks')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),

            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name'])
                    ->weight('semibold'),

                TextColumn::make('gender')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'M' => 'info',
                        'F' => 'pink',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state === 'M' ? 'Male' : 'Female'),

                TextColumn::make('position')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employment_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Contract'    => 'warning',
                        'Temporary'   => 'info',
                        'Consultancy' => 'primary',
                        'Other'       => 'gray',
                        default       => 'gray',
                    }),

                TextColumn::make('location.location_name')
                    ->label('Location')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('project.project_name')
                    ->label('Project')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('phone_number')
                    ->label('Phone')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->toggleable(),

                TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('basic_salary')
                    ->label('Basic Salary')
                    ->numeric(decimalPlaces: 2)
                    ->prefix('ETB ')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('date_of_employment')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('date_resigned')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('first_name')
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('gender')
                    ->options(['M' => 'Male', 'F' => 'Female']),

                SelectFilter::make('employment_type')
                    ->options([
                        'Contract'    => 'Contract',
                        'Temporary'   => 'Temporary',
                        'Consultancy' => 'Consultancy',
                        'Other'       => 'Other',
                    ]),

                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'project_name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            EmployeeResource\RelationManagers\LeaveRequestsRelationManager::class,
            EmployeeResource\RelationManagers\TimeRecordsRelationManager::class,
            EmployeeResource\RelationManagers\TravelRequestsRelationManager::class,
            EmployeeResource\RelationManagers\HrProcessesRelationManager::class,
            EmployeeResource\RelationManagers\PerformanceEvaluationsRelationManager::class,
            EmployeeResource\RelationManagers\PayrollRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'view'   => ViewEmployee::route('/{record}'),
            'edit'   => EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
