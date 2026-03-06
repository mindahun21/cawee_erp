<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                \Filament\Schemas\Components\Section::make('Supplier Details')
                    ->columns(2)
                    ->icon('heroicon-o-truck')
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('contact_person'),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email(),
                        TextInput::make('phone')
                            ->tel(),
                        TextInput::make('tax_id'),
                        Textarea::make('address')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
