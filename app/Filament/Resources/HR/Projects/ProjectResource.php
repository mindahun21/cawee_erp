<?php

namespace App\Filament\Resources\HR\Projects;

use App\Filament\Resources\HR\Projects\Pages\ManageProjects;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationParentItem = 'HR Settings';

    protected static ?string $navigationLabel = 'Projects';

    protected static ?int $navigationSort = 11;

    protected static ?string $recordTitleAttribute = 'project_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('project_name')
                ->required()
                ->maxLength(200),

            TextInput::make('project_code')
                ->required()
                ->maxLength(50)
                ->unique(ignoreRecord: true),

            Select::make('location_id')
                ->label('Location')
                ->relationship('location', 'location_name')
                ->searchable()
                ->preload()
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project_code')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('project_name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('location.location_name')
                    ->label('Location')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('employees_count')
                    ->label('# Employees')
                    ->counts('employees')
                    ->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProjects::route('/'),
        ];
    }
}
