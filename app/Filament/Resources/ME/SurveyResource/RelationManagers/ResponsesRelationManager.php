<?php

namespace App\Filament\Resources\ME\SurveyResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    protected static ?string $title = 'Responses';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('respondent_code')
                    ->placeholder('-'),
                TextColumn::make('location')
                    ->placeholder('-'),
                TextColumn::make('answers_count')
                    ->counts('answers')
                    ->label('Answers'),
            ]);
    }
}
