<?php

namespace App\Filament\Resources\CampaignEvents\CampaignEventResource\RelationManagers;

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

class VolunteersRelationManager extends RelationManager
{
    protected static string $relationship = 'volunteers';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('donor_id')
                    ->relationship('donor', 'first_name')
                    ->searchable(['first_name', 'last_name', 'organization_name'])
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('role')
                    ->required()
                    ->maxLength(100),
                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'registered' => 'Registered',
                        'confirmed' => 'Confirmed',
                        'attended' => 'Attended',
                        'cancelled' => 'Cancelled',
                        'no_show' => 'No Show',
                    ])
                    ->required()
                    ->default('registered'),
                TextInput::make('hours_committed')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('role')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'registered' => 'gray',
                        'confirmed' => 'info',
                        'attended' => 'success',
                        'cancelled' => 'danger',
                        'no_show' => 'warning',
                    }),
                TextColumn::make('hours_committed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('hours_completed')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Volunteer')
                    ->modalHeading('Add Volunteer')
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
