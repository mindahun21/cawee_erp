<?php

namespace App\Filament\Resources\CampaignEvents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CampaignEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('campaign.title')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('event_name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('event_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fundraiser' => 'success',
                        'meeting' => 'info',
                        'volunteer' => 'warning',
                        'awareness' => 'primary',
                        'other' => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'planned' => 'gray',
                        'confirmed' => 'info',
                        'ongoing' => 'success',
                        'completed' => 'primary',
                        'cancelled' => 'danger',
                    })
                    ->searchable(),
                TextColumn::make('event_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('venue')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('funds_raised')
                    ->money('ETB')
                    ->sortable(),
                TextColumn::make('volunteers_registered')
                    ->label('Volunteers')
                    ->numeric()
                    ->sortable()
                    ->description(fn ($record) => "of {$record->volunteers_needed} needed"),
                TextColumn::make('tickets_sold')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
