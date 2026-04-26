<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT;

use App\Filament\Resources\BRT\ProjectResource\Pages\CreateProject;
use App\Filament\Resources\BRT\ProjectResource\Pages\EditProject;
use App\Filament\Resources\BRT\ProjectResource\Pages\ListProjects;
use App\Filament\Resources\BRT\ProjectResource\Pages\ViewProject;
use App\Filament\Resources\BRT\ProjectResource\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\BRT\ProjectResource\RelationManagers\TrainingEventsRelationManager;
use App\Models\ME\MeProject;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class ProjectResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = MeProject::class;

    protected static ?string $modelLabel = 'Project';

    protected static ?string $pluralModelLabel = 'Projects';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string | \UnitEnum | null $navigationGroup = 'Beneficiary Registry & Project Tracking';

    protected static ?string $navigationLabel = 'Projects';

    protected static ?int $navigationSort = 0;

    protected static ?string $recordTitleAttribute = 'name';

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Project Overview')
                ->description('Core project identification and lifecycle details.')
                ->icon('heroicon-o-folder-open')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('project_code')
                        ->label('Project Code')
                        ->required()
                        ->maxLength(50)
                        ->unique(MeProject::class, 'project_code', ignoreRecord: true),

                    Select::make('status')
                        ->options([
                            'planning'  => 'Planning',
                            'active'    => 'Active',
                            'on_hold'   => 'On Hold',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('planning')
                        ->required(),

                    TextInput::make('project_type')
                        ->label('Project Type')
                        ->placeholder('e.g. Child Welfare, Livelihood, Nutrition…')
                        ->maxLength(80),

                    DatePicker::make('start_date')
                        ->native(false),

                    DatePicker::make('end_date')
                        ->native(false)
                        ->afterOrEqual('start_date'),
                ]),

            Section::make('Funding & Implementation')
                ->description('Donor, budget, and implementing organisation details.')
                ->icon('heroicon-o-currency-dollar')
                ->columns(2)
                ->schema([
                    TextInput::make('donor')
                        ->label('Donor / Funder')
                        ->maxLength(150)
                        ->placeholder('e.g. UNICEF, Save the Children…'),

                    TextInput::make('implementing_org')
                        ->label('Implementing Organisation')
                        ->maxLength(200),

                    TextInput::make('budget')
                        ->label('Total Budget')
                        ->numeric()
                        ->prefix(fn ($get) => $get('budget_currency') ?: 'ETB'),

                    Select::make('budget_currency')
                        ->label('Currency')
                        ->options([
                            'ETB' => 'Ethiopian Birr (ETB)',
                            'USD' => 'US Dollar (USD)',
                            'EUR' => 'Euro (EUR)',
                            'GBP' => 'British Pound (GBP)',
                        ])
                        ->default('ETB'),

                    TextInput::make('target_beneficiaries')
                        ->label('Target Beneficiaries')
                        ->numeric()
                        ->default(0),

                    TextInput::make('location')
                        ->label('Project Location(s)')
                        ->maxLength(255)
                        ->placeholder('Regions / kebeles covered'),

                    Select::make('manager_id')
                        ->label('Project Manager')
                        ->relationship('manager', 'name')
                        ->searchable()
                        ->preload(),
                ]),

            Section::make('Description')
                ->schema([
                    Textarea::make('description')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ── Infolist ──────────────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Project Overview')
                ->icon('heroicon-o-folder-open')
                ->columns(3)
                ->schema([
                    TextEntry::make('project_code')->badge()->color('primary'),
                    TextEntry::make('name'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (MeProject $record): string => $record->status_color),
                    TextEntry::make('project_type')->placeholder('—'),
                    TextEntry::make('start_date')->date()->placeholder('—'),
                    TextEntry::make('end_date')->date()->placeholder('—'),
                ]),

            Section::make('Funding & Implementation')
                ->icon('heroicon-o-currency-dollar')
                ->columns(3)
                ->schema([
                    TextEntry::make('donor')->placeholder('—'),
                    TextEntry::make('implementing_org')->label('Implementing Org')->placeholder('—'),
                    TextEntry::make('budget')
                        ->money(fn (MeProject $record): string => $record->budget_currency ?? 'ETB')
                        ->placeholder('—'),
                    TextEntry::make('target_beneficiaries')->label('Target Beneficiaries')->placeholder('—'),
                    TextEntry::make('location')->placeholder('—'),
                    TextEntry::make('manager.name')->label('Project Manager')->placeholder('—'),
                ]),

            Section::make('Description')
                ->schema([
                    TextEntry::make('description')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project_code')
                    ->label('Code')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (MeProject $record): string => $record->status_color)
                    ->sortable(),

                TextColumn::make('project_type')
                    ->label('Type')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('donor')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('enrollments_count')
                    ->counts('enrollments')
                    ->label('Beneficiaries')
                    ->sortable(),

                TextColumn::make('manager.name')
                    ->label('Manager')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'planning'  => 'Planning',
                        'active'    => 'Active',
                        'on_hold'   => 'On Hold',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    // ── Relations & Pages ─────────────────────────────────────────────────────

    public static function getRelationManagers(): array
    {
        return [
            EnrollmentsRelationManager::class,
            TrainingEventsRelationManager::class,
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
