<?php

namespace App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

class CandidatesRelationManager extends RelationManager
{
    protected static string $relationship = 'candidates';

    protected static ?string $recordTitleAttribute = 'first_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Not editable here, use the main form repeater for slots
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->label('Candidate Name')
                    ->formatStateUsing(fn ($record) => $record->full_name)
                    ->searchable(),
                TextColumn::make('last_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pivot.candidate_from_time')
                    ->label('Slot Start')
                    ->time('H:i'),
                TextColumn::make('pivot.candidate_to_time')
                    ->label('Slot End')
                    ->time('H:i'),
            ]);
    }
}
