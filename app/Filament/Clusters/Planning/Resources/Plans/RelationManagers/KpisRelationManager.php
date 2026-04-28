<?php

namespace App\Filament\Clusters\Planning\Resources\Plans\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class KpisRelationManager extends RelationManager
{
    protected static string $relationship = 'kpis';

    protected static ?string $title = 'Performance Indicators (KPIs)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('indicator_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('target_value')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('actual_value')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('unit')
                    ->maxLength(50),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('indicator_name')
            ->columns([
                Tables\Columns\TextColumn::make('indicator_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('target_value')
                    ->numeric(),
                Tables\Columns\TextColumn::make('actual_value')
                    ->numeric()
                    ->color(fn ($record) => $record->actual_value >= $record->target_value ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('unit'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
