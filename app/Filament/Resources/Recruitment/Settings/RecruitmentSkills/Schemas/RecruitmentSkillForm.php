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
                \Filament\Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique('recruitment_skill_categories', 'name')
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique('recruitment_skill_categories', 'slug')
                            ->maxLength(255),
                    ])
                    ->nullable(),
            ]);
    }
}

