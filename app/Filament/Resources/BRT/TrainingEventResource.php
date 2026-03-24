<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT;

use App\Filament\Resources\BRT\TrainingEventResource\Pages\CreateTrainingEvent;
use App\Filament\Resources\BRT\TrainingEventResource\Pages\EditTrainingEvent;
use App\Filament\Resources\BRT\TrainingEventResource\Pages\ListTrainingEvents;
use App\Filament\Resources\BRT\TrainingEventResource\Pages\ViewTrainingEvent;
use App\Filament\Resources\BRT\TrainingEventResource\RelationManagers\AttendancesRelationManager;
use App\Models\BRT\BrtTrainingEvent;
use App\Models\ME\MeBeneficiary;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TrainingEventResource extends Resource
{
    protected static ?string $model = BrtTrainingEvent::class;

    protected static ?string $modelLabel = 'Training / Event';

    protected static ?string $pluralModelLabel = 'Training & Events';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string | \UnitEnum | null $navigationGroup = 'Beneficiary Registry & Project Tracking';

    protected static ?string $navigationLabel = 'Training & Events';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Event Details')
                ->description('Capture the training or community event information.')
                ->icon('heroicon-o-calendar-days')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(200)
                        ->columnSpanFull(),

                    Select::make('event_type')
                        ->label('Event Type')
                        ->options([
                            'training'           => 'Training',
                            'workshop'           => 'Workshop',
                            'community_meeting'  => 'Community Meeting',
                            'awareness_campaign' => 'Awareness Campaign',
                            'support_group'      => 'Support Group Session',
                            'iga_session'        => 'IGA / Livelihood Session',
                            'other'              => 'Other',
                        ])
                        ->default('training')
                        ->required(),

                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->preload(),

                    DatePicker::make('event_date')
                        ->required()
                        ->native(false)
                        ->default(now()),

                    TimePicker::make('start_time')
                        ->label('Start Time')
                        ->native(false),

                    TimePicker::make('end_time')
                        ->label('End Time')
                        ->native(false)
                        ->after('start_time'),

                    TextInput::make('venue')
                        ->label('Venue / Location')
                        ->maxLength(200),

                    TextInput::make('facilitator')
                        ->label('Facilitator / Trainer')
                        ->maxLength(150),

                    Select::make('created_by')
                        ->label('Recorded By')
                        ->relationship('createdBy', 'name')
                        ->searchable()
                        ->preload(),
                ]),

            Section::make('Agenda & Notes')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Textarea::make('objectives')
                        ->label('Objectives')
                        ->rows(3),

                    Textarea::make('topics_covered')
                        ->label('Topics Covered')
                        ->rows(3),

                    Textarea::make('notes')
                        ->label('Additional Notes')
                        ->rows(3),
                ]),
        ]);
    }

    // ── Infolist ──────────────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Event Details')
                ->icon('heroicon-o-calendar-days')
                ->columns(3)
                ->schema([
                    TextEntry::make('event_code')->badge()->color('primary'),
                    TextEntry::make('title')->columnSpan(2),
                    TextEntry::make('event_type')
                        ->badge()
                        ->color(fn (BrtTrainingEvent $record): string => $record->event_type_color),
                    TextEntry::make('project.name')->label('Project')->placeholder('—'),
                    TextEntry::make('event_date')->date(),
                    TextEntry::make('start_time')->placeholder('—'),
                    TextEntry::make('end_time')->placeholder('—'),
                    TextEntry::make('venue')->placeholder('—'),
                    TextEntry::make('facilitator')->placeholder('—'),
                    TextEntry::make('createdBy.name')->label('Recorded By')->placeholder('—'),
                ]),

            Section::make('Agenda & Notes')
                ->icon('heroicon-o-document-text')
                ->columns(1)
                ->schema([
                    TextEntry::make('objectives')->placeholder('—'),
                    TextEntry::make('topics_covered')->label('Topics Covered')->placeholder('—'),
                    TextEntry::make('notes')->placeholder('—'),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event_code')
                    ->label('Code')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('event_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (BrtTrainingEvent $record): string => $record->event_type_color),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('event_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('venue')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('facilitator')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('attendances_count')
                    ->counts('attendances')
                    ->label('Attendees')
                    ->sortable(),
            ])
            ->defaultSort('event_date', 'desc')
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Type')
                    ->options([
                        'training'           => 'Training',
                        'workshop'           => 'Workshop',
                        'community_meeting'  => 'Community Meeting',
                        'awareness_campaign' => 'Awareness Campaign',
                        'support_group'      => 'Support Group',
                        'iga_session'        => 'IGA Session',
                        'other'              => 'Other',
                    ]),

                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    // ── Relations & Pages ─────────────────────────────────────────────────────

    public static function getRelationManagers(): array
    {
        return [
            AttendancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTrainingEvents::route('/'),
            'create' => CreateTrainingEvent::route('/create'),
            'view'   => ViewTrainingEvent::route('/{record}'),
            'edit'   => EditTrainingEvent::route('/{record}/edit'),
        ];
    }
}
