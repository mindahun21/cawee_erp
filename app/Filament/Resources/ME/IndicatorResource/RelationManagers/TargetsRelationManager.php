<?php

namespace App\Filament\Resources\ME\IndicatorResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TargetsRelationManager extends RelationManager
{
    protected static string $relationship = 'targets';

    protected static ?string $title = 'Targets';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('period_start')
                    ->required(),
                DatePicker::make('period_end')
                    ->required()
                    ->afterOrEqual('period_start'),
                TextInput::make('target_value')
                    ->required()
                    ->numeric()
                    ->minValue(0.01),
                TextInput::make('scope_location')
                    ->maxLength(255),
                TextInput::make('scope_project')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period_start')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_end')
                    ->date()
                    ->sortable(),
                TextColumn::make('target_value')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('scope_location')
                    ->placeholder('-'),
                TextColumn::make('scope_project')
                    ->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
