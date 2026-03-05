<?php

namespace App\Filament\Resources\JobPositions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class JobPositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('job_position')
                    ->required()
                    ->maxLength(255),

                Select::make('recruitment_skill_id')
                    ->label('Skill')
                    ->relationship('skill', 'name')
                    ->searchable()
                    ->preload(),

                Select::make('recruitment_industry_id')
                    ->label('Industry')
                    ->relationship('industry', 'name')
                    ->searchable()
                    ->preload(),

                Textarea::make('description')
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }
}
