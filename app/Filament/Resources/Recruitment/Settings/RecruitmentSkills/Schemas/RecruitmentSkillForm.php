<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentSkills\Schemas;

use Filament\Schemas\Schema;

class RecruitmentSkillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('category')
                    ->options([
                        'Technical' => 'Technical',
                        'Soft' => 'Soft Skill',
                        'Language' => 'Language',
                        'Other' => 'Other',
                    ])
                    ->nullable(),
            ]);
    }
}
