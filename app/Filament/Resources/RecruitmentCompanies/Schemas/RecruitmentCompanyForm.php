<?php

namespace App\Filament\Resources\RecruitmentCompanies\Schemas;

use Filament\Schemas\Schema;

class RecruitmentCompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')->required(),
                \Filament\Forms\Components\TextInput::make('address')->required(),
                \Filament\Forms\Components\Textarea::make('industry'),
                \Filament\Forms\Components\FileUpload::make('images')->image()->multiple(),
            ]);
    }
}
