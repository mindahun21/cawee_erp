<?php

namespace App\Filament\Resources\Recruitment\RecruitmentEvaluationCriterias\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RecruitmentEvaluationCriteriaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('criteria_type')
                    ->required(),
                TextInput::make('score_1_desc'),
                TextInput::make('score_2_desc'),
                TextInput::make('score_3_desc'),
                TextInput::make('score_4_desc'),
                TextInput::make('score_5_desc'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('created_by')
                    ->numeric(),
            ]);
    }
}
