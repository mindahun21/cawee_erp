<?php

namespace App\Filament\Resources\RecruitmentSkills\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RecruitmentSkillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Skill Name')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
