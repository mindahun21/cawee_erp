<?php

namespace App\Filament\Resources\HR\Settings;

use App\Models\Department;
use App\Models\JobPosition;
use App\Models\ContractType;
use App\Models\EducationLevel;
use App\Models\FieldOfStudy;
use App\Models\TrainingType;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * HR Settings Resource
 *
 * A single Filament resource that manages all HR lookup/settings tables
 * (departments, job positions, contract types, education levels, etc.)
 * from one unified page using a custom "sections" navigation.
 *
 * Individual sub-resources (DepartmentResource, JobPositionResource, etc.)
 * are kept as Filament resources registered under the HR Settings group.
 */
class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'HR Settings';

    protected static ?string $navigationLabel = 'Departments';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(150)
                    ->unique(Department::class, 'name', ignoreRecord: true),

                TextInput::make('code')
                    ->label('Short Code')
                    ->maxLength(20)
                    ->placeholder('e.g. FIN, HR, OPS'),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('code')->badge()->color('gray'),
                TextColumn::make('job_positions_count')->label('Positions')
                    ->counts('jobPositions')->alignCenter(),
                TextColumn::make('employees_count')->label('Employees')
                    ->counts('employees')->alignCenter(),
                TextColumn::make('description')->limit(50)->toggleable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDepartments::route('/'),
        ];
    }
}
