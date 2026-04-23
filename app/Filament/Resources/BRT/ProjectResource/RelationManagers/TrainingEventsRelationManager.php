<?php

declare(strict_types=1);

namespace App\Filament\Resources\BRT\ProjectResource\RelationManagers;

use App\Models\BRT\BrtTrainingEvent;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TrainingEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'trainingEvents';

    protected static ?string $title = 'Training & Events';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(200),

            Select::make('event_type')
                ->options([
                    'training'           => 'Training',
                    'workshop'           => 'Workshop',
                    'community_meeting'  => 'Community Meeting',
                    'awareness_campaign' => 'Awareness Campaign',
                    'support_group'      => 'Support Group',
                    'iga_session'        => 'IGA Session',
                    'other'              => 'Other',
                ])
                ->default('training')
                ->required(),

            DatePicker::make('event_date')
                ->required()
                ->native(false)
                ->default(now()),

            TimePicker::make('start_time')->native(false),
            TimePicker::make('end_time')->native(false),
            TextInput::make('venue')->maxLength(200),
            TextInput::make('facilitator')->maxLength(150),
            Textarea::make('objectives')->rows(2),
            Textarea::make('topics_covered')->rows(2),
            Textarea::make('notes')->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event_code')->badge()->color('primary'),
                TextColumn::make('title')->wrap()->searchable(),
                TextColumn::make('event_type')
                    ->badge()
                    ->color(fn (BrtTrainingEvent $record): string => $record->event_type_color),
                TextColumn::make('event_date')->date()->sortable(),
                TextColumn::make('venue')->placeholder('—'),
                TextColumn::make('attendances_count')->counts('attendances')->label('Attendees'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->defaultSort('event_date', 'desc');
    }
}
