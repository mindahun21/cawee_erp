<?php

namespace App\Filament\Resources\Recruitment\RecruitmentPlans\Tables;

use App\Models\Recruitment\RecruitmentPlan;
use App\Services\Recruitment\RecruitmentApprovalService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
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
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class RecruitmentPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
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
            ])
            ->recordActions([
                Action::make('submit_for_approval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(function (RecruitmentPlan $record) {
                        if ($record->status !== RecruitmentPlan::STATUS_DRAFT) {
                            return false;
                        }
                        if (RecruitmentApprovalService::hasBeenRejected($record)) {
                            return RecruitmentApprovalService::wasEditedAfterRejection($record);
                        }
                        return true;
                    })
                    ->action(function (RecruitmentPlan $record) {
                        DB::transaction(function () use ($record) {
                            $record->update(['status' => RecruitmentPlan::STATUS_SUBMITTED]);
                            RecruitmentApprovalService::initialise($record, 'recruitment_plan');
                        });
                        Notification::make()->success()->title('Plan Submitted for Approval')->send();
                    }),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (RecruitmentPlan $record) => RecruitmentApprovalService::canApprove(auth()->user(), $record, 'recruitment_plan'))
                    ->action(function (RecruitmentPlan $record) {
                        $pending = RecruitmentApprovalService::pendingRecordFor(auth()->user(), $record, 'recruitment_plan');
                        if ($pending) {
                            RecruitmentApprovalService::approve($record, 'recruitment_plan', $pending->stage_order, auth()->user());
                            Notification::make()->success()->title('Stage Approved')->send();
                        }
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
                        $pending = RecruitmentApprovalService::pendingRecordFor(auth()->user(), $record, 'recruitment_plan');
                        if ($pending) {
                            RecruitmentApprovalService::reject($record, 'recruitment_plan', $pending->stage_order, auth()->user(), $data['notes']);
                            Notification::make()->success()->title('Plan Rejected')->send();
                        }
                    }),

                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (RecruitmentPlan $record) => $record->isEditable()),
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
