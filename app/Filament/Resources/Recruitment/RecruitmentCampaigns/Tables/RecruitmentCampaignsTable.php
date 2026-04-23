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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use App\Filament\Helpers\ExportHelper;
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
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'rejected' => 'Rejected',
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'full' => 'Full',
                        'closed' => 'Closed',
                    ]),
                SelectFilter::make('employment_type')
                    ->options([
                        'full_time' => 'Full Time',
                        'part_time' => 'Part Time',
                        'contract' => 'Contract',
                        'internship' => 'Internship',
                    ]),
                SelectFilter::make('job_position_id')
                    ->relationship('jobPosition', 'title')
                    ->label('Job Position')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('channel_id')
                    ->relationship('channel', 'name')
                    ->label('Channel')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_public')
                    ->label('Public Campaign')
                    ->placeholder('All campaigns')
                    ->trueLabel('Public only')
                    ->falseLabel('Internal only'),
                SelectFilter::make('created_by')
                    ->label('Created By')
                    ->options(function () {
                        return \App\Models\User::pluck('name', 'id')->toArray();
                    })
                    ->searchable()
                    ->preload(),
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_from')
                            ->label('Start Date From'),
                        DatePicker::make('start_until')
                            ->label('Start Date Until'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when($data['start_from'], fn ($q, $date) => $q->whereDate('start_date', '>=', $date))
                            ->when($data['start_until'], fn ($q, $date) => $q->whereDate('start_date', '<=', $date));
                    }),
            ])
            ->filtersFormColumns(2)
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
                        if ($record->end_date && $record->end_date < today()) {
                            return false;
                        }
                        if ($record->recruitment_plan_id) {
                            $plan = $record->recruitmentPlan;
                            if (!$plan || $plan->status === \App\Models\Recruitment\RecruitmentPlan::STATUS_CLOSED || ($plan->end_date && $plan->end_date < today())) {
                                return false;
                            }
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
            ->bulkActions([
                ExportHelper::makeBulkAction('export'),
                DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('Delete:RecruitmentCampaign'))
                    ->requiresConfirmation()
                    ->modalHeading('Delete Selected Campaigns')
                    ->modalDescription('Are you sure you want to delete the selected campaigns? This will also delete all related applications, interview schedules, offers, and other associated data.')
                    ->modalSubmitActionLabel('Yes, delete them')
                    ->deselectRecordsAfterCompletion(),
                BulkActionGroup::make([
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
