<?php

namespace App\Filament\Resources\HR\Settings;

use App\Models\JobPosition;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JobPositionResource extends Resource
{
    protected static ?string $model = JobPosition::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationParentItem = 'HR Settings';

    protected static ?string $navigationLabel = 'Job Positions';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    'sm' => 2,
                ])->schema([
                TextInput::make('title')
                    ->label('Position Title')
                    ->required()
                    ->maxLength(150),

                Select::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Select::make('grade_id')
                    ->label('Grade / Level')
                    ->relationship('grade', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('vacancy_count')
                    ->numeric()
                    ->default(1),

                Toggle::make('is_active')
                    ->label('Published for Recruitment')
                    ->default(true),

                Grid::make(2)->schema([
                    TextInput::make('salary_min')
                        ->numeric()
                        ->prefix('ETB'),
                    TextInput::make('salary_max')
                        ->numeric()
                        ->prefix('ETB'),
                ]),

                RichEditor::make('description')
                    ->columnSpanFull(),

                RichEditor::make('requirements')
                    ->columnSpanFull(),

                Select::make('skills')
                    ->label('Required Skills')
                    ->relationship('skills', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('department.name')->label('Department')->badge()->color('info'),
                TextColumn::make('grade.name')->label('Grade')->badge()->color('gray')->sortable(),
                TextColumn::make('employees_count')->label('Employees')
                    ->counts('employees')->alignCenter(),
                TextColumn::make('vacancy_count')
                    ->label('Vacancies')
                    ->sortable()
                    ->alignCenter(),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('General Information')
                ->columnSpanFull()
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('title')
                            ->label('Position Title')
                            ->weight('bold'),
                        TextEntry::make('department.name')
                            ->label('Department'),
                        TextEntry::make('grade.name')
                            ->label('Grade / Level'),
                        TextEntry::make('vacancy_count')
                            ->label('Vacancies Allocated'),
                        TextEntry::make('salary_min')
                            ->label('Min Salary')
                            ->money('ETB'),
                        TextEntry::make('salary_max')
                            ->label('Max Salary')
                            ->money('ETB'),
                    ]),
                ]),

            Section::make('Recruitment Content')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('description')
                        ->label('Job Description')
                        ->html()
                        ->columnSpanFull(),
                    TextEntry::make('requirements')
                        ->label('Job Requirements')
                        ->html()
                        ->columnSpanFull(),
                ]),

            Section::make('Required Skills')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('skills.name')
                        ->label('Skills')
                        ->badge()
                        ->color('primary')
                        ->separator(', '),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageJobPositions::route('/'),
        ];
    }
}
