<?php

namespace App\Filament\Resources\RecruitmentCandidates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RecruitmentCandidatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile_picture')
                    ->circular()
                    ->label('Photo'),

                TextColumn::make('candidate_code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('first_name')
                    ->label('Full Name')
                    ->formatStateUsing(fn($record) => $record->first_name . ' ' . $record->last_name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                TextColumn::make('contact_email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('contact_phone')
                    ->searchable(),

                TextColumn::make('desired_salary')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('recruitment_campaign_id')
                    ->relationship('campaign', 'campaign_name')
                    ->label('Campaign'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
