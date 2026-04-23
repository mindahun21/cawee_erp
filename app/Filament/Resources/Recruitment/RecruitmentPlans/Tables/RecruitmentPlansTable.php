<?php

namespace App\Filament\Resources\Recruitment\RecruitmentPlans\Tables;

use App\Models\Recruitment\RecruitmentPlan;
use App\Services\Recruitment\RecruitmentApprovalService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use App\Filament\Helpers\ExportHelper;
use Filament\Tables\Table;

class RecruitmentPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('jobPosition.title')
                    ->label('Position')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('vacancies_needed')
                    ->label('Vacancies')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('working_from')
                    ->label('Type')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('manager.name')
                    ->label('Manager')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label('Start')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('End')
                    ->date()
                    ->sortable(),
                TextColumn::make('budget')
                    ->money('ETB')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => RecruitmentPlan::statusLabel($state))
                    ->color(fn (string $state) => RecruitmentPlan::statusColor($state))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options([
                        RecruitmentPlan::STATUS_DRAFT => 'Draft',
                        RecruitmentPlan::STATUS_SUBMITTED => 'Submitted',
                        RecruitmentPlan::STATUS_APPROVED => 'Approved',
                        RecruitmentPlan::STATUS_REJECTED => 'Rejected',
                        RecruitmentPlan::STATUS_CLOSED => 'Closed',
                    ]),
                SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Department')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('job_position_id')
                    ->relationship('jobPosition', 'title')
                    ->label('Job Position')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('working_from')
                    ->options([
                        'Internship' => 'Internship',
                        'Full-Time'  => 'Full-Time',
                        'Part-Time'  => 'Part-Time',
                        'Contract'   => 'Contract',
                        'Temporary'  => 'Temporary',
                    ]),
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
                Action::make('submit_for_approval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(function (RecruitmentPlan $record) {
                        if ($record->created_by !== auth()->id()) {
                            return false;
                        }
                        if ($record->status !== RecruitmentPlan::STATUS_DRAFT) {
                            return false;
                        }
                        if ($record->end_date && $record->end_date < today()) {
                            return false;
                        }
                        if (RecruitmentApprovalService::hasBeenRejected($record)) {
                            return RecruitmentApprovalService::wasEditedAfterRejection($record);
                        }
                        return true;
                    })
                    ->action(function (RecruitmentPlan $record) {
                        RecruitmentApprovalService::submitForApproval($record);
                        Notification::make()->success()->title('Plan Submitted for Approval')->send();
                    }),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (RecruitmentPlan $record) => RecruitmentApprovalService::canApprove(auth()->user(), $record, 'recruitment_plan'))
                    ->action(function (RecruitmentPlan $record) {
                        RecruitmentApprovalService::approveStage($record, auth()->user());
                        Notification::make()->success()->title('Stage Approved')->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Recruitment Plan')
                    ->form([
                        Textarea::make('notes')
                            ->label('Reason for Rejection')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->visible(fn (RecruitmentPlan $record) => RecruitmentApprovalService::canApprove(auth()->user(), $record, 'recruitment_plan'))
                    ->action(function (RecruitmentPlan $record, array $data) {
                        RecruitmentApprovalService::rejectStage($record, auth()->user(), $data['notes']);
                        Notification::make()->success()->title('Plan Rejected')->send();
                    }),

                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (RecruitmentPlan $record) => $record->isEditable()),
                DeleteAction::make()
                    ->visible(fn (RecruitmentPlan $record) => $record->status === RecruitmentPlan::STATUS_DRAFT),
            ])
            ->bulkActions([
                ExportHelper::makeBulkAction('export'),
                DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('Delete:RecruitmentPlan'))
                    ->requiresConfirmation()
                    ->modalHeading('Delete Selected Plans')
                    ->modalDescription('Are you sure you want to delete the selected plans? This will also delete all related campaigns, applications, and other associated data.')
                    ->modalSubmitActionLabel('Yes, delete them')
                    ->deselectRecordsAfterCompletion(),
                BulkActionGroup::make([
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
