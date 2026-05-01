<?php

namespace App\Filament\Resources\Campaigns\CampaignResource\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('event_name')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('event_type')
                    ->options([
                        'fundraiser' => 'Fundraiser',
                        'meeting' => 'Meeting',
                        'volunteer' => 'Volunteer',
                        'awareness' => 'Awareness',
                        'other' => 'Other',
                    ])
                    ->required()
                    ->default('fundraiser'),
                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'planned' => 'Planned',
                        'confirmed' => 'Confirmed',
                        'ongoing' => 'Ongoing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('planned'),
                \Filament\Forms\Components\DateTimePicker::make('event_date')
                    ->required(),
                TextInput::make('venue')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Textarea::make('venue_address')
                    ->required()
                    ->rows(2),
                TextInput::make('budget')
                    ->numeric()
                    ->default(0)
                    ->prefix('ETB'),
                TextInput::make('actual_cost')
                    ->numeric()
                    ->default(0)
                    ->prefix('ETB'),
                TextInput::make('funds_raised')
                    ->numeric()
                    ->default(0)
                    ->prefix('ETB'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event_name')
            ->columns([
                TextColumn::make('event_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event_type')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'planned' => 'gray',
                        'confirmed' => 'info',
                        'ongoing' => 'success',
                        'completed' => 'primary',
                        'cancelled' => 'danger',
                    }),
                TextColumn::make('event_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('venue')
                    ->searchable(),
                TextColumn::make('funds_raised')
                    ->money()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Event')
                    ->modalHeading('Add Event')
                    ->icon('heroicon-o-plus'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
