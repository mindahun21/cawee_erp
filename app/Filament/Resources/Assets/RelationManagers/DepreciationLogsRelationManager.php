<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepreciationLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'depreciationLogs';

    protected static ?string $title = 'Depreciation Schedule';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('period_date')
                    ->required()
                    ->label('Period'),
                TextInput::make('depreciation_amount')
                    ->required()
                    ->numeric()
                    ->prefix('ETB')
                    ->label('Depreciation Amount'),
                TextInput::make('book_value')
                    ->required()
                    ->numeric()
                    ->prefix('ETB')
                    ->label('Net Book Value'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('period_date')
            ->defaultSort('period_date', 'desc')
            ->columns([
                TextColumn::make('asset.name')
                    ->label('Asset'),
                TextColumn::make('asset.depreciation.name')
                    ->label('Depreciation Type'),
                TextColumn::make('asset.depreciation.months')
                    ->label('Total Months'),
                TextColumn::make('period_date')
                    ->label('Period')
                    ->date('M Y')
                    ->sortable(),
                TextColumn::make('depreciation_amount')
                    ->label('Monthly Depr.')
                    ->money('ETB')
                    ->sortable(),
                TextColumn::make('book_value')
                    ->label('Current Value')
                    ->money('ETB')
                    ->sortable(),
                TextColumn::make('asset.remaining_months')
                    ->label('Remaining Months'),
                TextColumn::make('created_at')
                    ->label('Posted On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Post Depreciation'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
