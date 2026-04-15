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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use App\Filament\Helpers\ExportHelper;
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
                SelectFilter::make('status')
                    ->options([
                        'applied' => 'Applied',
                        'under_review' => 'Under Review',
                        'shortlisted' => 'Shortlisted',
                        'interview_scheduled' => 'Interview Scheduled',
                        'interviewed' => 'Interviewed',
                        'offer_pending' => 'Offer Pending',
                        'offer_accepted' => 'Offer Accepted',
                        'offer_declined' => 'Offer Declined',
                        'hired' => 'Hired',
                        'rejected' => 'Rejected',
                        'withdrawn' => 'Withdrawn',
                    ]),
                SelectFilter::make('campaign_id')
                    ->relationship('campaign', 'title')
                    ->label('Campaign')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('candidate_id')
                    ->relationship('candidate', 'first_name')
                    ->label('Candidate')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')) ?: '—'),
                SelectFilter::make('reviewed_by')
                    ->label('Reviewer')
                    ->relationship('reviewer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('shortlisted_by')
                    ->label('Shortlister')
                    ->relationship('shortlister', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('applied_at')
                    ->form([
                        DatePicker::make('applied_from')
                            ->label('Applied From'),
                        DatePicker::make('applied_until')
                            ->label('Applied Until'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when($data['applied_from'], fn ($q, $date) => $q->whereDate('applied_at', '>=', $date))
                            ->when($data['applied_until'], fn ($q, $date) => $q->whereDate('applied_at', '<=', $date));
                    }),
            ])
            ->filtersFormColumns(2)
            ->recordUrl(fn ($record) => \App\Filament\Resources\Recruitment\RecruitmentApplications\RecruitmentApplicationResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                ExportHelper::makeBulkAction('export'),
                DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('Delete:RecruitmentApplication'))
                    ->requiresConfirmation()
                    ->modalHeading('Delete Selected Applications')
                    ->modalDescription('Are you sure you want to delete the selected applications? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete them')
                    ->deselectRecordsAfterCompletion(),
                BulkActionGroup::make([
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
