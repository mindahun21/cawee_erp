<?php

namespace App\Filament\Resources\Recruitment\RecruitmentCampaigns\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Recruitment\RecruitmentApplication;
use Illuminate\Support\Facades\Mail;
use App\Mail\Recruitment\CandidateRejectedMail;

class EvaluatedApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';
    protected static ?string $title = 'Evaluated Applications';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where(fn ($q) => 
                $q->whereIn('status', [
                    RecruitmentApplication::STATUS_INTERVIEWED,
                    RecruitmentApplication::STATUS_SELECTED,
                    RecruitmentApplication::STATUS_WAITLISTED,
                    RecruitmentApplication::STATUS_OFFER_PENDING,
                    RecruitmentApplication::STATUS_OFFER_ACCEPTED,
                    RecruitmentApplication::STATUS_OFFER_DECLINED,
                    RecruitmentApplication::STATUS_HIRED,
                ])
                ->orWhere(fn ($sq) => 
                    $sq->where('status', RecruitmentApplication::STATUS_REJECTED)
                        ->whereHas('candidate.evaluations', fn ($ev) => 
                            $ev->whereHas('schedule', fn ($sc) => 
                                $sc->where('campaign_id', $this->getOwnerRecord()->id)
                            )
                        )
                )
            ))
            ->columns([
                Tables\Columns\TextColumn::make('candidate.full_name')
                    ->label('Candidate Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_score')
                    ->label('Avg Interview Score')
                    ->getStateUsing(function ($record) {
                        $evals = \App\Models\Recruitment\RecruitmentCandidateEvaluation::where('candidate_id', $record->candidate_id)
                            ->whereHas('schedule', fn($q) => $q->where('campaign_id', $record->campaign_id))
                            ->get();
                        
                        if ($evals->isEmpty()) return 'N/A';
                        return number_format($evals->avg('overall_score'), 2);
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'N/A' ? 'gray' : ((float)$state >= 4 ? 'success' : ((float)$state >= 3 ? 'warning' : 'danger'))),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        RecruitmentApplication::STATUS_INTERVIEWED => 'primary',
                        RecruitmentApplication::STATUS_SELECTED => 'success',
                        RecruitmentApplication::STATUS_WAITLISTED => 'warning',
                        RecruitmentApplication::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied On')
                    ->date(),
            ])
            ->actions([
                \Filament\Actions\Action::make('mark_selected')
                    ->label('Select Candidate')
                    ->icon('heroicon-o-star')
                    ->color('success')
                    ->visible(fn ($record) => 
                        in_array($record->status, [RecruitmentApplication::STATUS_INTERVIEWED, RecruitmentApplication::STATUS_WAITLISTED])
                        && \App\Models\Recruitment\RecruitmentCandidateEvaluation::where('candidate_id', $record->candidate_id)
                            ->whereHas('schedule', fn($q) => $q->where('campaign_id', $this->getOwnerRecord()->id))
                            ->exists()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Mark Candidate as Selected')
                    ->modalDescription('Are you sure you want to select this candidate? They will be moved to the Offer Creation pipeline.')
                    ->action(function ($record) {
                        $record->update(['status' => RecruitmentApplication::STATUS_SELECTED]);
                        \Filament\Notifications\Notification::make()
                            ->title('Candidate selected for position!')
                            ->success()
                            ->send();
                    }),
                \Filament\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => 
                        in_array($record->status, [RecruitmentApplication::STATUS_INTERVIEWED, RecruitmentApplication::STATUS_WAITLISTED])
                        && \App\Models\Recruitment\RecruitmentCandidateEvaluation::where('candidate_id', $record->candidate_id)
                            ->whereHas('schedule', fn($q) => $q->where('campaign_id', $this->getOwnerRecord()->id))
                            ->exists()
                    )
                    ->form([
                        \Filament\Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason (Optional)')
                            ->placeholder('Describe why the candidate was not selected... this will be sent in the email.')
                    ])
                    ->modalHeading('Reject Application')
                    ->modalDescription('Are you sure you want to reject this candidate? A polite rejection email will be sent automatically.')
                    ->action(function ($record, array $data) {
                        $record->update(['status' => RecruitmentApplication::STATUS_REJECTED]);
                        
                        // Send rejection email
                        if ($record->candidate?->email) {
                            Mail::to($record->candidate->email)->queue(new CandidateRejectedMail($record, $data['rejection_reason'] ?? null));
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Candidate rejected and notified.')
                            ->danger()
                            ->send();
                    }),
                \Filament\Actions\Action::make('create_offer')
                    ->label('Create Offer')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->visible(fn ($record) => 
                        $record->status === RecruitmentApplication::STATUS_SELECTED
                        && !$record->offer()->exists()
                        && \App\Models\Recruitment\RecruitmentCandidateEvaluation::where('candidate_id', $record->candidate_id)
                            ->whereHas('schedule', fn($q) => $q->where('campaign_id', $this->getOwnerRecord()->id))
                            ->exists()
                    )
                    ->form([
                        \Filament\Forms\Components\TextInput::make('offered_salary')
                            ->label('Offered Salary')
                            ->numeric()
                            ->prefix('ETB')
                            ->nullable(),
                        \Filament\Forms\Components\DatePicker::make('offer_date')
                            ->label('Offer Date')
                            ->default(now()->toDateString())
                            ->required(),
                        \Filament\Forms\Components\DatePicker::make('offer_expiry_date')
                            ->label('Offer Expiry Date')
                            ->nullable(),
                        \App\Filament\Resources\Recruitment\RecruitmentOffers\RecruitmentOfferResource::getApprovalWorkflowSelect(),
                        
                        \Filament\Forms\Components\FileUpload::make('offer_letter_path')
                            ->label('Offer Letter (Optional)')
                            ->disk('private')
                            ->directory('offer-letters')
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->nullable(),

                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Custom Message/Notes')
                            ->placeholder('Add a custom message for the candidate...')
                            ->nullable(),
                    ])
                    ->modalHeading('Create Employment Offer')
                    ->action(function ($record, array $data) {
                        $offer = \App\Models\Recruitment\RecruitmentOffer::create([
                            'application_id'      => $record->id,
                            'offered_salary'      => $data['offered_salary'] ?? null,
                            'offer_date'          => $data['offer_date'],
                            'offer_expiry_date'   => $data['offer_expiry_date'] ?? null,
                            'offer_letter_path'   => $data['offer_letter_path'] ?? null,
                            'notes'               => $data['notes'] ?? null,
                            'status'              => \App\Models\Recruitment\RecruitmentOffer::STATUS_DRAFT,
                            'issued_by'           => auth()->id(),
                            'approval_workflow_id' => $data['approval_workflow_id'] ?? null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Offer created! Submit it for approval to notify the candidate.')
                            ->success()
                            ->send();

                        $this->redirect(
                            \App\Filament\Resources\Recruitment\RecruitmentOffers\RecruitmentOfferResource::getUrl('view', ['record' => $offer])
                        );
                    }),

                \Filament\Actions\Action::make('view_offer')
                    ->label('View Offer')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->visible(fn ($record) => $record->offer()->exists())
                    ->url(fn ($record) => 
                        \App\Filament\Resources\Recruitment\RecruitmentOffers\RecruitmentOfferResource::getUrl('view', [
                            'record' => $record->offer
                        ])
                    ),

                \Filament\Actions\Action::make('waitlist')
                    ->label('Add to Waitlist')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->visible(fn ($record) => 
                        $record->status === RecruitmentApplication::STATUS_SELECTED
                        && !$record->offer()->exists()
                        && \App\Models\Recruitment\RecruitmentCandidateEvaluation::where('candidate_id', $record->candidate_id)
                            ->whereHas('schedule', fn($q) => $q->where('campaign_id', $this->getOwnerRecord()->id))
                            ->exists()
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => RecruitmentApplication::STATUS_WAITLISTED]);
                        \Filament\Notifications\Notification::make()
                            ->title('Candidate moved to waitlist.')
                            ->warning()
                            ->send();
                    }),
                \Filament\Actions\Action::make('view_comparison')
                    ->label('Compare Data')
                    ->icon('heroicon-o-chart-bar-square')
                    ->url(fn ($record) => \App\Filament\Resources\Recruitment\RecruitmentApplications\RecruitmentApplicationResource::getUrl('comparison', ['record' => $record])),
            ]);
    }
}
