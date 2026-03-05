<?php

namespace App\Filament\Resources\EvaluationCriterias\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EvaluationCriteriasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('criteria_name')
                    ->label('Criteria Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('criteria_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'group criteria' => 'success',
                        'criteria' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('user.name')
                    ->label('Added By')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Added Date')
                    ->dateTime('M d, Y')
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
