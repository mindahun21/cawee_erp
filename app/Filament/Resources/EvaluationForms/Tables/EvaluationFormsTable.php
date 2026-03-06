<?php

namespace App\Filament\Resources\EvaluationForms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EvaluationFormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('added_by.name')
                    ->label('Added By')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('form_name')
                    ->label('Form Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('position.job_position')
                    ->label('Position')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('groups.criteria_count')
                    ->label('Number of Criteria')
                    ->getStateUsing(function ($record) {
                        // Sum all criteria in all groups
                        return $record->groups->sum(fn($group) => $group->criteria->count());
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Added Date')
                    ->dateTime('d M Y H:i')
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
