<?php

namespace App\Filament\Resources\RecruitmentIndustries\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;

class RecruitmentIndustryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                ->label('Industry Name')
                ->required()
                ->maxLength(255),
            ]);
    }
}
