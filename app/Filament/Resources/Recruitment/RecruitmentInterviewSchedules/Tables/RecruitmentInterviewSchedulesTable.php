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
            ->defaultSort('interview_date', 'desc');
    }
}
