<?php

namespace App\Filament\Resources\Recruitment\RecruitmentApplications\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RecruitmentApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('candidate.first_name')
                    ->label('Candidate')
                    ->formatStateUsing(fn ($record) => trim(($record->candidate?->first_name ?? '') . ' ' . ($record->candidate?->last_name ?? '')) ?: '—')
                    ->searchable(),
                TextColumn::make('campaign.title')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'applied' => 'info',
                        'under_review' => 'warning',
                        'shortlisted' => 'success',
                        'interview_scheduled' => 'primary',
                        'offer_pending' => 'warning',
                        'offer_accepted', 'hired' => 'success',
                        'offer_declined', 'rejected' => 'danger',
                        'withdrawn' => 'gray',
                        default => 'secondary',
                    })
                    ->searchable(),
                TextColumn::make('applied_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->sortable(),
                TextColumn::make('shortlister.name')
                    ->label('Shortlisted By')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordUrl(fn ($record) => \App\Filament\Resources\Recruitment\RecruitmentApplications\RecruitmentApplicationResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make(),
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
