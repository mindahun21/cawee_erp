<?php

namespace App\Filament\Resources\OtherSettings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OtherSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('show_recruitment_plan')
                    ->label('Show Recruitment Plan')
                    ->default(false),

                Toggle::make('display_quantity_on_portal')
                    ->label('Display Quantity on Portal')
                    ->default(false),

                Toggle::make('send_welcome_email')
                    ->label('Send Welcome Email')
                    ->default(false),

                TextInput::make('candidate_code_prefix')
                    ->label('Candidate Code Prefix')
                    ->maxLength(10)
                    ->nullable(),

                TextInput::make('next_candidate_code_number')
                    ->label('Next Candidate Code Number')
                    ->numeric()
                    ->required()
                    ->default(1),
            ]);
    }
}
