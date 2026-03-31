<?php

namespace App\Filament\Resources\HR\Employees;

use App\Filament\Resources\HR\Employees\Pages\CreateEmployee;
use App\Filament\Resources\HR\Employees\Pages\EditEmployee;
use App\Filament\Resources\HR\Employees\Pages\ListEmployees;
use App\Filament\Resources\HR\Employees\Pages\ViewEmployee;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\FieldOfStudy;
use App\Models\JobPosition;
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

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'phone_number', 'national_id'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->tabs([

                Tab::make('Personal Information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Section::make()->columns(2)->schema([
                            Select::make('user_id')
                                ->label('Linked System User')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->helperText('Link this employee to a system user account (for My Profile access).')
                                ->columnSpanFull(),
                            TextInput::make('first_name')
                                ->required()
                                ->maxLength(100),

                            TextInput::make('last_name')
                                ->required()
                                ->maxLength(100),

                            Select::make('gender')
                                ->options([
                                    'Male' => 'Male',
                                    'Female' => 'Female',
                                    'Other' => 'Other',
                                ])
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

                            \Ysfkaya\FilamentPhoneInput\Forms\PhoneInput::make('phone_number')
                                ->defaultCountry('ET')
                                ->nullable(),

                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->maxLength(150),

                            Select::make('education_level_id')
                                ->label('Education Level')
                                ->relationship('educationLevel', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable(),

                            Select::make('field_of_study_id')
                                ->label('Field of Study')
                                ->relationship('fieldOfStudy', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable(),
                        ]),
                    ]),

                Tab::make('Employment Details')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Section::make()->columns(2)->schema([
                            Select::make('department_id')
                                ->label('Department')
                                ->relationship('department', 'name')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->nullable(),

                            Select::make('job_position_id')
                                ->label('Job Position')
                                ->relationship('jobPosition', 'title', fn ($query, $get) =>
                                    $get('department_id')
                                        ? $query->where('department_id', $get('department_id'))
                                        : $query
                                )
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->helperText('Filter by department above to narrow the list.'),

                            Select::make('grade_id')
                                ->label('Grade')
                                ->relationship('grade', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable(),

                            Select::make('contract_type_id')
                                ->label('Contract Type')
                                ->relationship('contractType', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable(),

                            Select::make('employment_type')
                                ->options([
                                    'Permanent' => 'Permanent',
                                    'Contract' => 'Contract',
                                    'Temporary' => 'Temporary',
                                    'Consultancy' => 'Consultancy',
                                ])
                                ->nullable(),

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
                                ->default(0)
                                ->minValue(0)
                                ->required(),

                            TextInput::make('transport_allowance')
                                ->numeric()
                                ->prefix('ETB')
                                ->default(0)
                                ->minValue(0),

                            TextInput::make('house_allowance')
                                ->numeric()
                                ->prefix('ETB')
                                ->default(0)
                                ->minValue(0),

                            TextInput::make('communication_allowance')
                                ->numeric()
                                ->prefix('ETB')
                                ->default(0)
                                ->minValue(0),

                            TextInput::make('overtime_allowance')
                                ->numeric()
                                ->prefix('ETB')
                                ->default(0)
                                ->minValue(0),

                            TextInput::make('incentive')
                                ->numeric()
                                ->prefix('ETB')
                                ->default(0)
                                ->minValue(0),

                            TextInput::make('other_allowances')
                                ->numeric()
                                ->prefix('ETB')
                                ->default(0)
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
                    ->color(fn ($state): string => match ($state) {
                        'Male' => 'info',
                        'Female' => 'pink',
                        default => 'gray',
                    }),

                TextColumn::make('jobPosition.title')
                    ->label('Position')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employment_type')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'Permanent'   => 'success',
                        'Contract'    => 'warning',
                        'Temporary'   => 'info',
                        'Consultancy' => 'primary',
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
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                        'Other' => 'Other',
                    ]),

                SelectFilter::make('employment_type')
                    ->options([
                        'Permanent' => 'Permanent',
                        'Contract' => 'Contract',
                        'Temporary' => 'Temporary',
                        'Consultancy' => 'Consultancy',
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
