<?php

namespace App\Filament\Resources\RecruitmentInterviews\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RecruitmentInterviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('interview_schedule_name')
                    ->label('Interview Schedule Name')
                    ->searchable(),

                TextColumn::make('from_hour')
                    ->label('Time')
                    ->formatStateUsing(
                        fn($record) =>
                        $record->from_hour . ' - ' . $record->to_hour
                    ),

                TextColumn::make('interview_date')
                    ->date(),

                TextColumn::make('recruitment_campaign')
                    ->searchable(),

                TextColumn::make('candidates.candidate')
                    ->label('Candidate')
                    ->listWithLineBreaks(),

                TextColumn::make('interviewer')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Added Date')
                    ->dateTime(),

                TextColumn::make('added_by.name')
                    ->label('Added By'),

            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
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
