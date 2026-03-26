<?php

namespace App\Filament\Resources\Recruitment\RecruitmentCampaigns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use App\Services\Recruitment\RecruitmentApprovalService;
use App\Models\Recruitment\RecruitmentCampaign;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class RecruitmentCampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jobPosition.title')
                    ->label('Job Position')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('employment_type')
                    ->searchable(),
                TextColumn::make('vacancies_needed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_public')
                    ->boolean(),

                // Hidden by default — accessible via column editor
                TextColumn::make('campaign_code')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('channel.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('recruitmentPlan.jobPosition.title')
                    ->label('Linked Plan')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('salary_min')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('salary_max')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('currency')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('display_salary')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('manager.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('meta_title')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('candidate_gender')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('candidate_literacy')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('candidate_seniority')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->recordActions([
                ViewAction::make(),
                Action::make('submit_for_approval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(function (RecruitmentCampaign $record) {
                        if ($record->created_by !== auth()->id()) {
                            return false;
                        }
                        if ($record->status !== RecruitmentCampaign::STATUS_DRAFT) {
                            return false;
                        }
                        if (RecruitmentApprovalService::hasBeenRejected($record)) {
                            return RecruitmentApprovalService::wasEditedAfterRejection($record);
                        }
                        return true;
                    })
                    ->action(function (RecruitmentCampaign $record) {
                        RecruitmentApprovalService::submitForApproval($record);
                        Notification::make()->success()->title('Campaign Submitted for Approval')->send();
                    }),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (RecruitmentCampaign $record) => RecruitmentApprovalService::canApprove(auth()->user(), $record, 'recruitment_campaign'))
                    ->action(function (RecruitmentCampaign $record) {
                        RecruitmentApprovalService::approveStage($record, auth()->user());
                        Notification::make()->success()->title('Stage Approved')->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Recruitment Campaign')
                    ->form([
                        Textarea::make('notes')
                            ->label('Reason for Rejection')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->visible(fn (RecruitmentCampaign $record) => RecruitmentApprovalService::canApprove(auth()->user(), $record, 'recruitment_campaign'))
                    ->action(function (RecruitmentCampaign $record, array $data) {
                        RecruitmentApprovalService::rejectStage($record, auth()->user(), $data['notes']);
                        Notification::make()->success()->title('Campaign Rejected')->send();
                    }),

                EditAction::make()
                    ->visible(fn (RecruitmentCampaign $record) => $record->isEditable()),
                DeleteAction::make()
                    ->visible(fn (RecruitmentCampaign $record) => $record->status === RecruitmentCampaign::STATUS_DRAFT),
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
