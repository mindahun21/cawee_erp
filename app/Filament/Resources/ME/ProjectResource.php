<?php

namespace App\Filament\Resources\ME;

use App\Filament\Resources\ME\ProjectResource\Pages\CreateProject;
use App\Filament\Resources\ME\ProjectResource\Pages\EditProject;
use App\Filament\Resources\ME\ProjectResource\Pages\ListProjects;
use App\Filament\Resources\ME\ProjectResource\Pages\ViewProject;
use App\Filament\Resources\ME\ProjectResource\RelationManagers\FeedbackRelationManager;
use App\Filament\Resources\ME\Support\MeAuditTrail;
use App\Models\ME\MeProject;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = MeProject::class;

    protected static ?string $modelLabel = 'Project';

    protected static ?string $pluralModelLabel = 'Projects';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring and Evaluation';

    protected static ?string $navigationLabel = 'Projects (Legacy)';

    protected static ?int $navigationSort = 0;

    /**
     * Project management has been moved to the BRT module.
     * This resource is kept for backward compatibility but hidden from navigation.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('project_code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        DatePicker::make('start_date'),
                        DatePicker::make('end_date')
                            ->afterOrEqual('start_date'),
                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('project_code'),
                        TextEntry::make('name'),
                        TextEntry::make('start_date')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('end_date')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('description')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                MeAuditTrail::section('me_projects'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project_code')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('indicators_count')
                    ->counts('indicators')
                    ->label('Indicators'),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            FeedbackRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'view'   => ViewProject::route('/{record}'),
            'edit'   => EditProject::route('/{record}/edit'),
        ];
    }
}
