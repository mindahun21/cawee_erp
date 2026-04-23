<?php

namespace App\Filament\Resources\Recruitment\RecruitmentChannels\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RecruitmentChannelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('type')
                    ->required(),
                TextInput::make('form_schema'),
                Select::make('responsible_person_id')
                    ->relationship('responsiblePerson', 'name'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
