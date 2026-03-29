<?php

namespace App\Filament\Resources\Recruitment\RecruitmentCandidates\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\Recruitment\RecruitmentApplication;
use App\Services\Recruitment\RecruitmentApprovalService;
use Filament\Notifications\Notification;
use App\Models\User;

class ApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('campaign_id')
                    ->relationship('campaign', 'title')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'primary' => RecruitmentApplication::STATUS_APPLIED,
                        'warning' => RecruitmentApplication::STATUS_UNDER_REVIEW,
                        'success' => fn ($state) => in_array($state, [RecruitmentApplication::STATUS_SHORTLISTED, RecruitmentApplication::STATUS_HIRED]),
                        'danger' => fn ($state) => in_array($state, [RecruitmentApplication::STATUS_REJECTED, RecruitmentApplication::STATUS_WITHDRAWN]),
                    ]),
                TextColumn::make('applied_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                Action::make('submit_for_screening')
                    ->label('Begin Screening')
                    ->color('primary')
                    ->icon('heroicon-o-play')
                    ->requiresConfirmation()
                    ->visible(fn (RecruitmentApplication $record) => 
                        $record->status === RecruitmentApplication::STATUS_APPLIED
                    )
                    ->action(function (RecruitmentApplication $record) {
                        RecruitmentApprovalService::initialise($record, 'recruitment_application');
                        $record->update(['status' => RecruitmentApplication::STATUS_UNDER_REVIEW]);
                        Notification::make()->title('Screening started.')->success()->send();
                    }),
                
                Action::make('approve_stage')
                    ->label('Shortlist / Advance')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(function (RecruitmentApplication $record) {
                        if ($record->status === RecruitmentApplication::STATUS_WITHDRAWN) {
                            return false;
                        }
                        /** @var User $user */
                        $user = auth()->user();
                        return RecruitmentApprovalService::canApprove($user, $record, 'recruitment_application');
                    })
                    ->form([
                        Textarea::make('notes')->label('Evaluation Notes'),
                    ])
                    ->action(function (array $data, RecruitmentApplication $record) {
                        /** @var User $user */
                        $user = auth()->user();
                        $pending = RecruitmentApprovalService::pendingRecordFor($user, $record, 'recruitment_application');
                        RecruitmentApprovalService::approve($record, 'recruitment_application', $pending->stage_order, $user, $data['notes'] ?? null);
                        
                        if (RecruitmentApprovalService::isFullyApproved($record, 'recruitment_application')) {
                            $record->update(['status' => RecruitmentApplication::STATUS_HIRED]);
                            Notification::make()->title('Candidate Advanced/Hired successfully.')->success()->send();
                        } else {
                            $record->update(['status' => RecruitmentApplication::STATUS_SHORTLISTED]);
                            Notification::make()->title('Application advanced to next stage.')->success()->send();
                        }
                    }),

                Action::make('reject_stage')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(function (RecruitmentApplication $record) {
                        if ($record->status === RecruitmentApplication::STATUS_WITHDRAWN) {
                            return false;
                        }
                        /** @var User $user */
                        $user = auth()->user();
                        return RecruitmentApprovalService::canApprove($user, $record, 'recruitment_application');
                    })
                    ->form([
                        Textarea::make('notes')->label('Rejection Reason')->required(),
                    ])
                    ->action(function (array $data, RecruitmentApplication $record) {
                        /** @var User $user */
                        $user = auth()->user();
                        $pending = RecruitmentApprovalService::pendingRecordFor($user, $record, 'recruitment_application');
                        RecruitmentApprovalService::reject($record, 'recruitment_application', $pending->stage_order, $user, $data['notes']);
                        
                        $record->update([
                            'status' => RecruitmentApplication::STATUS_REJECTED,
                            'rejection_reason' => $data['notes'],
                        ]);
                        Notification::make()->title('Candidate Rejected.')->danger()->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
