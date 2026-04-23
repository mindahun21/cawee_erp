<?php

namespace App\Filament\Resources\Recruitment\RecruitmentChannels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Helpers\ExportHelper;
use Filament\Tables\Table;

class RecruitmentChannelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('responsiblePerson.name')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(function () {
                        return \App\Models\Recruitment\RecruitmentChannel::distinct()
                            ->whereNotNull('type')
                            ->pluck('type', 'type')
                            ->toArray();
                    })
                    ->searchable(),
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All channels')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                SelectFilter::make('responsible_person_id')
                    ->label('Responsible Person')
                    ->relationship('responsiblePerson', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                ExportHelper::makeBulkAction('export'),
                DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('Delete:RecruitmentChannel'))
                    ->requiresConfirmation()
                    ->modalHeading('Delete Selected Channels')
                    ->modalDescription('Are you sure you want to delete the selected channels? This may affect campaigns using these channels.')
                    ->modalSubmitActionLabel('Yes, delete them')
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
