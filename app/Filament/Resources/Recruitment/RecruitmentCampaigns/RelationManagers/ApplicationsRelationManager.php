<?php

namespace App\Filament\Resources\Recruitment\RecruitmentCampaigns\RelationManagers;

use App\Models\Recruitment\RecruitmentApplication;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\Recruitment\RecruitmentApplications\RecruitmentApplicationResource;

class ApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';

    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('candidate.full_name')
                    ->label('Candidate')
                    ->getStateUsing(fn ($record) => trim(($record->candidate?->first_name ?? '') . ' ' . ($record->candidate?->last_name ?? '')))
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('candidate', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'applied' => 'info',
                        'under_review' => 'warning',
                        'shortlisted' => 'success',
                        'interview_scheduled' => 'primary',
                        'interviewed' => 'primary',
                        'selected' => 'success',
                        'waitlisted' => 'warning',
                        'offer_pending' => 'warning',
                        'offer_accepted', 'hired' => 'success',
                        'offer_declined', 'rejected' => 'danger',
                        'withdrawn' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('applied_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->sortable(),
                TextColumn::make('shortlister.name')
                    ->label('Shortlisted By')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'applied' => 'Applied',
                        'under_review' => 'Under Review',
                        'shortlisted' => 'Shortlisted',
                        'interview_scheduled' => 'Interview Scheduled',
                        'interviewed' => 'Interviewed',
                        'selected' => 'Selected',
                        'waitlisted' => 'Waitlisted',
                        'offer_pending' => 'Offer Pending',
                        'offer_accepted' => 'Offer Accepted',
                        'offer_declined' => 'Offer Declined',
                        'hired' => 'Hired',
                        'rejected' => 'Rejected',
                        'withdrawn' => 'Withdrawn',
                    ]),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()
                    ->url(fn ($record) => RecruitmentApplicationResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ])
            ->recordUrl(fn ($record) => RecruitmentApplicationResource::getUrl('view', ['record' => $record]));
    }
}
