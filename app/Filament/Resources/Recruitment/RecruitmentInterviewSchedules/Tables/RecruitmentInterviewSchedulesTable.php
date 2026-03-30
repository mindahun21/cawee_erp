<?php

namespace App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RecruitmentInterviewSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('campaign.title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('interview_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('from_time')
                    ->time('H:i')
                    ->label('Start'),
                TextColumn::make('to_time')
                    ->time('H:i')
                    ->label('End'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'scheduled' => 'success',
                        'completed' => 'primary',
                        'cancelled' => 'danger',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'scheduled' => 'Scheduled',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'rejected' => 'Rejected',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Assigned To')
                    ->options(function () {
                        $users = \App\Models\User::where('id', '!=', auth()->id())->pluck('name', 'id')->toArray();
                        return ['me' => 'Me'] + $users;
                    })
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        
                        $userId = $data['value'] === 'me' ? auth()->id() : $data['value'];
                        
                        return $query->whereHas('scheduleInterviewers', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                    }),
                \Filament\Tables\Filters\Filter::make('day')
                    ->form([
                        \Filament\Forms\Components\Select::make('day_filter')
                            ->label('Day')
                            ->options([
                                'today' => 'Today',
                                'this_week' => 'This Week',
                                'next_week' => 'Next Week',
                                'this_month' => 'This Month',
                            ])
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query->when($data['day_filter'], function ($query, $filter) {
                            match ($filter) {
                                'today' => $query->whereDate('interview_date', now()),
                                'this_week' => $query->whereBetween('interview_date', [now()->startOfWeek(), now()->endOfWeek()]),
                                'next_week' => $query->whereBetween('interview_date', [now()->addWeek()->startOfWeek(), now()->addWeek()->endOfWeek()]),
                                'this_month' => $query->whereMonth('interview_date', now()->month)->whereYear('interview_date', now()->year),
                                default => $query,
                            };
                        });
                    }),
            ])
            ->filtersFormColumns(3)
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'rejected'])),
                \Filament\Actions\Action::make('submit')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'rejected']))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        \App\Services\Recruitment\RecruitmentApprovalService::submitForApproval($record);
                        \Filament\Notifications\Notification::make()->title('Schedule submitted for approval')->success()->send();
                    }),
            ])
            ->defaultSort('interview_date', 'desc');
    }
}
