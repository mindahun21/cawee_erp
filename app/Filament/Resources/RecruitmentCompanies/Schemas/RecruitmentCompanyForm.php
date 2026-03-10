<?php

namespace App\Filament\Resources\RecruitmentCompanies\Schemas;

use Filament\Schemas\Schema;

class RecruitmentCompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')->label('Company Name')->required(),
                \Filament\Forms\Components\TextInput::make('address')->label('Company Address')->required(),
                \Filament\Forms\Components\Textarea::make('industry')->label('Company Industry'),
                \Filament\Forms\Components\FileUpload::make('images')->label('Company Image')->image()->multiple(),
            ]);
    }
}
